<template>
    <div class="mixlens-analyzer">
        <!-- Header -->
        <header>
            <h1>MixLens Analyzer</h1>

            <form @submit.prevent="analyzeMix">
                <input
                    type="url"
                    v-model="soundcloudUrl"
                    placeholder="SoundCloud mix URL"
                    required
                />
                <button :disabled="loading">
                    {{ loading ? 'Analyzing…' : 'Analyze' }}
                </button>
            </form>
        </header>

        <!-- Error -->
        <p v-if="error" class="error">{{ error }}</p>

        <!-- Player -->
        <section v-if="ready" class="player">
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

        <!-- Tracklist -->
        <section v-if="tracks.length" class="tracklist">
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
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount, nextTick, onMounted } from 'vue'
import WaveSurfer from 'wavesurfer.js'

interface Track {
    title: string
    artist: string
    album: string | null
    timestamp: number | null
}

interface AnalyzeResponse {
    tracks: Track[]
}

const soundcloudUrl = ref('')
const tracks = ref<Track[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const ready = ref(false)
const isPlaying = ref(false)

const waveformEl = ref<HTMLDivElement | null>(null)
let wavesurfer: WaveSurfer | null = null

const MOCK_AUDIO_URL = '/audio/berlioz_sample.wav'

const analyzeMix = async () => {
    loading.value = true
    error.value = null
    tracks.value = []
    ready.value = false

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
            }),
        })

        const data: AnalyzeResponse = await response.json()
        tracks.value = data.tracks

        ready.value = true
        await nextTick()
        await nextTick()
        
        console.log('About to init waveform, element:', waveformEl.value)
        initWaveform()
        ready.value = true
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
    console.log('initWaveform called')
    console.log('waveformEl.value:', waveformEl.value)
    
    if (!waveformEl.value) {
        console.error('Waveform element not found!')
        return
    }
    
    console.log('Creating WaveSurfer instance...')

    if (!waveformEl.value) return

    wavesurfer?.destroy()

    wavesurfer = WaveSurfer.create({
        container: waveformEl.value,
        waveColor: '#cbd5e1',
        progressColor: '#0f172a',
        height: 96,
        barWidth: 2,
        cursorWidth: 1,
    })

    wavesurfer.on('error', (err) => {
        error.value = `Audio load failed: ${err}`
        console.error('WaveSurfer error:', err)
    })

    wavesurfer.on('play', () => (isPlaying.value = true))
    wavesurfer.on('pause', () => (isPlaying.value = false))

    wavesurfer.load(MOCK_AUDIO_URL)
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
})
</script>

<style scoped>
.mixlens-analyzer {
    max-width: 960px;
    margin: 2rem auto;
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
}

button {
    padding: 0.6rem 1rem;
}

.player {
    margin: 1.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.play-button {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    background-color: #0f172a;
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: background-color 0.2s;
}

.play-button:hover {
    background-color: #1e293b;
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
    margin-top: 1.5rem;
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

.error {
    color: #dc2626;
}
</style>
