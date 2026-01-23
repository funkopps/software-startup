<template>
    <div class="mixlens-analyzer">
        <div class="analyzer-header">
            <!-- Header -->
            <header>
                <form @submit.prevent="analyzeMix">
                    <input
                        type="url"
                        v-model="soundcloudUrl"
                        placeholder="SoundCloud mix URL"
                        required
                    />
                    <button class="analyze-button" :disabled="loading">
                        {{ loading ? 'Analyzing…' : 'Analyze' }}
                    </button>
                </form>
            </header>

            <form @submit.prevent>
                <input
                    type="url"
                    v-model="soundcloudUrl"
                    placeholder="SoundCloud mix URL"
                    required
                />

                <input
                    type="file"
                    accept="audio/*"
                    @change="onFileChange"
                    required
                />
            </form>
        </header>

                <div ref="waveformEl" class="waveform"></div>
            </div>

        <!-- Player -->
        <section v-if="previewReady" class="player">
            <button @click="togglePlayback" class="play-button">
                <svg v-if="!isPlaying" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                </svg>
            </button>

            <div ref="waveformEl" class="waveform"></div>
        </section>

        <section v-if="previewReady" class="interval">
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
            >
                {{ loading ? 'Analyzing…' : (analysisDone ? 'Re-analyze selection' : 'Analyze selection') }}
            </button>
        </section>

        <!-- Tracklist -->
        <section v-if="analysisDone && tracks.length" class="tracklist">
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
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount, nextTick, onMounted } from 'vue'
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

const startTime = ref<number | null>(null)
const endTime = ref<number | null>(null)

const uploadedFilePath = ref<string | null>(null)

const waveformEl = ref<HTMLDivElement | null>(null)
let wavesurfer: WaveSurfer | null = null
let activeRegion: any = null


const onFileChange = async (event: Event) => {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]

    if (!file) return

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
        console.log('[MixAnalyzer] sending request')
        const controller = new AbortController()
        const timeoutId = window.setTimeout(() => {
            console.warn('[MixAnalyzer] request timeout, aborting')
            controller.abort()
        }, 15000)

        const response = await fetch('/analyze-mix', {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
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

        window.clearTimeout(timeoutId)
        console.log('[MixAnalyzer] response received', {
            status: response.status,
            ok: response.ok,
        })
        const data: AnalyzeResponse = await response.json()
        console.log('[MixAnalyzer] response data', data)
        tracks.value = data.tracks
        analysisDone.value = true
    } catch (e) {
        console.error('[MixAnalyzer] analyzeMix error', e)
        error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
        console.log('[MixAnalyzer] analyzeMix finally')
        loading.value = false
    }
}

onMounted(() => {
    console.log('[MixAnalyzer] Component mounted')
})

const initWaveform = () => {
    if (!waveformEl.value || !audioObjectUrl.value) return

    wavesurfer?.destroy()

    wavesurfer = WaveSurfer.create({
        container: waveformEl.value,
        waveColor: '#ffd9c2',
        progressColor: '#ff7a2d',
        height: 96,
        barWidth: 2,
        cursorWidth: 1,
    })

    const regions = wavesurfer.registerPlugin(
        RegionsPlugin.create()
    )

    regions.enableDragSelection({
        color: 'rgba(15, 23, 42, 0.25)',
        drag: true,
        resize: true,
    })

    wavesurfer.on('ready', () => {
        const duration = wavesurfer!.getDuration()

        startTime.value = 0
        endTime.value = Math.min(30, duration)

        activeRegion = regions.addRegion({
            start: startTime.value,
            end: endTime.value,
            color: 'rgba(15, 23, 42, 0.25)',
            drag: true,
            resize: true,
        })
    })

    regions.on('region-created', (region) => {
        if (activeRegion && region.id !== activeRegion.id) {
            activeRegion.remove()
        }

        activeRegion = region
        startTime.value = region.start
        endTime.value = region.end
    })

    regions.on('region-updated', (region) => {
        activeRegion = region
        startTime.value = region.start
        endTime.value = region.end
    })

    wavesurfer.load(audioObjectUrl.value)
}

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
    max-width: 960px;
    margin: 0 auto;
    font-family: system-ui, sans-serif;
}

header {
    margin-bottom: 1.5rem;
}

form {
    display: flex;
    gap: 0.5rem;
}

input {
    flex: 1;
    padding: 0.6rem;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    padding-left: 1.1rem;
}

input::placeholder {
    color: #9ca3af;
}

button {
    padding: 0.6rem 1rem;
}

.analyze-button {
    background-color: #ff7a2d;
    color: #0a0a0a;
    border: none;
    border-radius: 999px;
    cursor: pointer;
}

.analyze-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.analyzer-results {
    margin-top: 3.5rem;
    padding: 1.5rem 1.75rem;
    background: #1f1f1f;
    border: 1px solid #ffffff;
    border-radius: 24px;
    box-shadow: 0 10px 24px rgba(255, 255, 255, 0.25);
    min-height: 180px;
}

.analyzer-results.is-empty {
    opacity: 0;
    pointer-events: none;
}

.player {
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.play-button {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    background-color: #ff7a2d;
    color: #0a0a0a;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: background-color 0.2s;
}

.play-button:hover {
    background-color: #ff984f;
    color: #0a0a0a;
}

.play-button svg {
    width: 24px;
    height: 24px;
}

.waveform {
    flex: 1;
}

.tracklist {
    border-top: 1px solid #111827;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
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
