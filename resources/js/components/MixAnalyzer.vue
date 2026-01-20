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

            <!-- Error -->
            <p v-if="error" class="error">{{ error }}</p>
        </div>

        <section
            class="analyzer-results"
            :class="{ 'is-empty': !ready && !tracks.length }"
        >
            <!-- Player -->
            <div v-show="ready" class="player">
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

            <!-- Tracklist -->
            <div v-show="tracks.length" class="tracklist">
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
    console.log('[MixAnalyzer] analyzeMix start', {
        url: soundcloudUrl.value,
    })
    loading.value = true
    error.value = null
    tracks.value = []
    ready.value = false

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
            }),
        })

        window.clearTimeout(timeoutId)
        console.log('[MixAnalyzer] response received', {
            status: response.status,
            ok: response.ok,
        })
        const data: AnalyzeResponse = await response.json()
        console.log('[MixAnalyzer] response data', data)
        tracks.value = data.tracks

        ready.value = true
        await nextTick()
        await nextTick()
        
        console.log('[MixAnalyzer] About to init waveform', waveformEl.value)
        initWaveform()
        ready.value = true
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
    console.log('[MixAnalyzer] initWaveform called')
    console.log('[MixAnalyzer] waveformEl.value', waveformEl.value)
    
    if (!waveformEl.value) {
        console.error('[MixAnalyzer] Waveform element not found!')
        return
    }
    
    console.log('[MixAnalyzer] Creating WaveSurfer instance...')

    if (!waveformEl.value) return

    wavesurfer?.destroy()

    wavesurfer = WaveSurfer.create({
        container: waveformEl.value,
        waveColor: '#ffd9c2',
        progressColor: '#ff7a2d',
        height: 96,
        barWidth: 2,
        cursorWidth: 1,
    })

    wavesurfer.on('error', (err) => {
        error.value = `Audio load failed: ${err}`
        console.error('[MixAnalyzer] WaveSurfer error', err)
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

.error {
    color: #dc2626;
}
</style>
