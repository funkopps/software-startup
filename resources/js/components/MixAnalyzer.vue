<template>
    <div class="mixlens-analyzer">
        <section class="panel input-panel">
            <div class="panel-header">
                <span class="panel-step">Step 1</span>
                <h2 class="panel-title">Load your mix</h2>
            </div>
            <form class="input-form" @submit.prevent>
                <input
                    type="url"
                    v-model="soundcloudUrl"
                    placeholder="https://soundcloud.com/..."
                    required
                    class="url-input"
                />

                <div class="file-field">
                    <label class="file-label" for="mix-analyzer-file">
                        {{ selectedFileName || 'Choose file' }}
                    </label>
                    <input
                        id="mix-analyzer-file"
                        type="file"
                        accept="audio/*"
                        @change="onFileChange"
                        required
                        class="file-input"
                    />
                </div>
            </form>
        </section>

        <!-- Error -->
        <p v-if="error" class="error">{{ error }}</p>

        <!-- Player -->
        <section v-if="previewReady" class="panel player-panel">
            <div class="panel-header">
                <span class="panel-step">Step 2</span>
                <h2 class="panel-title">Preview and select</h2>
            </div>
            <div class="player">
                <button @click="togglePlayback" class="play-button">
                    <svg v-if="!isPlaying" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                    </svg>
                </button>

                <div ref="waveformEl" class="waveform"></div>
            </div>
        </section>

        <section v-if="previewReady" class="panel interval-panel">
            <div class="panel-header">
                <span class="panel-step">Step 3</span>
                <h2 class="panel-title">Define the range</h2>
            </div>
            <label>
                Start (seconds)
                <input
                    type="number"
                    min="0"
                    step="0.1"
                    v-model.number="startTime"
                />
            </label>

            <label>
                End (seconds)
                <input
                    type="number"
                    min="0"
                    step="0.1"
                    v-model.number="endTime"
                />
            </label>

            <button
                @click="analyzeMix"
                :disabled="loading || startTime === null || endTime === null"
                class="analyze-button"
            >
                {{ loading ? 'Analyzing…' : (analysisDone ? 'Re-analyze selection' : 'Analyze selection') }}
            </button>
        </section>

        <!-- Tracklist -->
        <section
            v-if="analysisDone && tracks.length"
            class="panel tracklist-panel"
            ref="resultsPanel"
        >
            <div class="panel-header">
                <span class="panel-step">Step 4</span>
                <h2 class="panel-title">Results</h2>
            </div>
            <div class="tracklist">
                <div
                    v-for="(track, index) in tracks"
                    :key="index"
                    class="track"
                    @click="seekTo(track.timestamp)"
                >
                    <span class="time">{{ formatTime(track.timestamp) }}</span>
                    <span class="title">
                        {{ track.artist }} – {{ track.title }}
                    </span>
                    <span class="score">{{ track.score }}</span>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount, nextTick, onMounted, watch } from 'vue'
import WaveSurfer from 'wavesurfer.js'
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js'

interface Track {
    title: string
    artist: string
    album: string | null
    timestamp: number | null
    score: number | null
}

interface AnalyzeResponse {
    tracks: Track[]
}

const audioFile = ref<File | null>(null)
const audioObjectUrl = ref<string | null>(null)
const soundcloudUrl = ref('')
const tracks = ref<Track[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const isPlaying = ref(false)
const previewReady = ref(false)
const analysisDone = ref(false)
const selectedFileName = ref('')

const startTime = ref<number | null>(null)
const endTime = ref<number | null>(null)

const uploadedFilePath = ref<string | null>(null)

const waveformEl = ref<HTMLDivElement | null>(null)
const resultsPanel = ref<HTMLElement | null>(null)
let wavesurfer: WaveSurfer | null = null
let activeRegion: any = null
let syncingFromRegion = false

const gradientStops = ['#f59e0b', '#f43f5e', '#8b5cf6']

const renderGradientWaveform = (
    peaks: Array<Float32Array | number[]>,
    ctx: CanvasRenderingContext2D
) => {
    const { width, height } = ctx.canvas
    const middle = height / 2
    const channel = peaks[0] ?? []
    const total = channel.length || 1
    const barWidth = 2
    const barGap = 1
    const step = barWidth + barGap
    const gradient = ctx.createLinearGradient(0, 0, width, 0)

    ctx.clearRect(0, 0, width, height)

    gradientStops.forEach((color, index) => {
        const stop =
            gradientStops.length === 1
                ? 1
                : index / (gradientStops.length - 1)
        gradient.addColorStop(stop, color)
    })

    ctx.fillStyle = gradient

    for (let x = 0; x < width; x += step) {
        const idx = Math.floor((x / width) * total)
        const value = Math.abs(channel[idx] ?? 0)
        const barHeight = Math.max(1, value * middle)

        ctx.fillRect(x, middle - barHeight, barWidth, barHeight * 2)
    }
}

const syncRegionFromInputs = () => {
    if (syncingFromRegion || !wavesurfer || !activeRegion) return
    if (startTime.value === null || endTime.value === null) return

    const duration = wavesurfer.getDuration()
    const start = Number(Math.max(0, Math.min(startTime.value, duration)).toFixed(1))
    const end = Number(Math.max(0, Math.min(endTime.value, duration)).toFixed(1))

    if (end <= start) return

    const startDiff = Math.abs(activeRegion.start - start)
    const endDiff = Math.abs(activeRegion.end - end)

    if (startDiff < 0.05 && endDiff < 0.05) return

    activeRegion.setOptions({ start, end })
    activeRegion.element?.style.setProperty('border', '1px solid #ffffff')
}

const onFileChange = async (event: Event) => {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]

    if (!file) {
        selectedFileName.value = ''
        return
    }

    selectedFileName.value = file.name

    loading.value = true
    error.value = null

    try {
        // Delete previous uploaded file
        if (uploadedFilePath.value) {
            await fetch('/delete-audio', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content ?? '',
                },
                body: JSON.stringify({
                    file_path: uploadedFilePath.value,
                }),
            })

            uploadedFilePath.value = null
        }

        // Upload new file
        audioFile.value = file

        const formData = new FormData()
        formData.append('audio_file', file)

        const response = await fetch('/upload-audio', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                        ?.content ?? '',
            },
            body: formData,
        })

        if (!response.ok) {
            throw new Error('File upload failed')
        }

        const data = await response.json()
        uploadedFilePath.value = data.file_path

        if (audioObjectUrl.value) {
            URL.revokeObjectURL(audioObjectUrl.value)
        }

        audioObjectUrl.value = URL.createObjectURL(file)

        previewReady.value = true
        analysisDone.value = false
        tracks.value = []

        await nextTick()
        initWaveform()
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
        loading.value = false
    }
}

const analyzeMix = async () => {
    if (!uploadedFilePath.value) {
        error.value = 'No uploaded file available'
        return
    }

    if (startTime.value === null || endTime.value === null) {
        error.value = 'Please set a start and end time'
        return
    }


    loading.value = true
    error.value = null
    tracks.value = []
    analysisDone.value = false

    try {
        const response = await fetch('/analyze-mix', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                        ?.content ?? '',
            },
            body: JSON.stringify({
                soundcloud_url: soundcloudUrl.value,
                file_path: uploadedFilePath.value,
                start_time: startTime.value,
                end_time: endTime.value,
            }),
        })
        
        // const raw = await response.text()
        // console.log('RAW RESPONSE:', raw)

        // throw new Error('Stop here')

        if (!response.ok) {
            const text = await response.text()
            throw new Error(text)
        }

        const data: AnalyzeResponse = await response.json()
        tracks.value = data.tracks
        analysisDone.value = true
        await nextTick()
        resultsPanel.value?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        })
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    console.log('Component mounted')
})

const initWaveform = () => {
    if (!waveformEl.value || !audioObjectUrl.value) return

    wavesurfer?.destroy()

    wavesurfer = WaveSurfer.create({
        container: waveformEl.value,
        waveColor: gradientStops,
        progressColor: '#ffffff',
        cursorColor: '#ffffff',
        height: 96,
        barWidth: 2,
        cursorWidth: 1,
        renderFunction: renderGradientWaveform,
    })

    const regions = wavesurfer.registerPlugin(
        RegionsPlugin.create()
    )

    regions.enableDragSelection({
        color: 'rgba(255, 255, 255, 0)',
        drag: true,
        resize: true,
    })

    wavesurfer.on('ready', () => {
        const duration = wavesurfer!.getDuration()

        startTime.value = 0
        endTime.value = Number(Math.min(30, duration).toFixed(1))

        activeRegion = regions.addRegion({
            start: startTime.value,
            end: endTime.value,
            color: 'rgba(255, 255, 255, 0)',
            drag: true,
            resize: true,
        })
        activeRegion.element.style.border = '1px solid #ffffff'
    })

    regions.on('region-created', (region) => {
        if (activeRegion && region.id !== activeRegion.id) {
            activeRegion.remove()
        }

        activeRegion = region
        activeRegion.element.style.border = '1px solid #ffffff'
        syncingFromRegion = true
        startTime.value = Number(region.start.toFixed(1))
        endTime.value = Number(region.end.toFixed(1))
        syncingFromRegion = false
    })

    regions.on('region-updated', (region) => {
        activeRegion = region
        activeRegion.element.style.border = '1px solid #ffffff'
        syncingFromRegion = true
        startTime.value = Number(region.start.toFixed(1))
        endTime.value = Number(region.end.toFixed(1))
        syncingFromRegion = false
    })

    wavesurfer.load(audioObjectUrl.value)
}

watch([startTime, endTime], () => {
    syncRegionFromInputs()
})

const togglePlayback = () => {
    wavesurfer?.playPause()
}

const seekTo = (timestamp: number | null) => {
    if (!timestamp || !wavesurfer) return
    wavesurfer.setTime(timestamp / 1000)
    wavesurfer.play()
}

const formatTime = (ms: number | null): string => {
    if (!ms) return '--:--'
    const totalSeconds = Math.floor(ms / 1000)
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60
    return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

onBeforeUnmount(() => {
    wavesurfer?.destroy()

    if (audioObjectUrl.value) {
        URL.revokeObjectURL(audioObjectUrl.value)
    }
})
</script>

<style scoped>
.mixlens-analyzer {
    margin: 2rem auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    font-family: system-ui, sans-serif;
}

.panel {
    border: 1px solid #262626;
    background: #0f0f0f;
    border-radius: 20px;
    padding: 1.5rem;
}

.panel-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.panel-step {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #a3a3a3;
}

.panel-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #ffffff;
}

.input-form {
    display: flex;
    gap: 0.75rem;
    flex-direction: column;
    align-items: center;
}

input {
    flex: 1;
    padding: 0.6rem;
    border-radius: 9999px;
    border: 1px solid #ffffff;
    background: #0f0f0f;
    color: #ffffff;
}

.url-input {
    width: 100%;
    text-align: center;
}

.file-input {
    position: absolute;
    inset: 0;
    width: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-field {
    position: relative;
    width: 100%;
}

.file-label {
    width: 100%;
    padding: 0.6rem;
    border-radius: 9999px;
    border: 1px solid #ffffff;
    background: #0f0f0f;
    color: #ffffff;
    text-align: center;
    cursor: pointer;
    display: block;
}

button {
    padding: 0.6rem 1rem;
}

.analyze-button {
    border-radius: 9999px;
    border: 1px solid #f97316;
    color: #f97316;
    background: transparent;
    transition: background-color 0.2s ease;
}

.analyze-button:hover:enabled {
    background-color: rgba(249, 115, 22, 0.12);
}

.analyze-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.interval-panel {
    display: grid;
    gap: 1rem;
}

.interval-panel label {
    display: grid;
    gap: 0.5rem;
    color: #d4d4d4;
}

.player {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: transparent;
}

.play-button {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    background-color: transparent;
    color: #ffffff;
    border: 2px solid transparent;
    border-radius: 9999px;
    background-image: linear-gradient(#0f0f0f, #0f0f0f),
        linear-gradient(90deg, #f59e0b, #f43f5e, #8b5cf6);
    background-origin: border-box;
    background-clip: padding-box, border-box;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: filter 0.2s;
}

.play-button:hover {
    filter: brightness(1.1);
}

.play-button svg {
    width: 24px;
    height: 24px;
}

.waveform {
    flex: 1;
}

.tracklist {
    border-top: 1px solid #e5e7eb;
    margin-top: 1rem;
}

.track {
    display: flex;
    gap: 1rem;
    padding: 0.5rem 0;
    cursor: pointer;
}

.track:hover {
    background: #232323;
}

.time {
    width: 48px;
    color: #64748b;
    font-variant-numeric: tabular-nums;
}

.title {
    font-weight: 500;
}

.score {
    font-weight: 500;
}

.error {
    color: #dc2626;
}
</style>
