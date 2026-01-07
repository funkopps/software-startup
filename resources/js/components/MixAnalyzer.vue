<template>
    <div class="mix-analyzer">
        <h1>DJ Mix Analyzer (MVP)</h1>

        <form @submit.prevent="analyzeMix">
            <label>
                SoundCloud URL
                <input
                    type="url"
                    v-model="soundcloudUrl"
                    placeholder="https://soundcloud.com/..."
                    required
                />
            </label>

            <button :disabled="loading">
                {{ loading ? 'Analyzing...' : 'Analyze Mix' }}
            </button>
        </form>

        <p v-if="error" class="error">{{ error }}</p>

        <table v-if="tracks.length" class="tracklist">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Artist</th>
                    <th>Title</th>
                    <th>Album</th>
                    <th>Release Date</th>
                    <th>Score</th>
                    <th>Spotify Track ID</th>
                    <th>Timestamp (ms)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(track, index) in tracks" :key="index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ track.artist }}</td>
                    <td>{{ track.title }}</td>
                    <td>{{ track.album ?? '-' }}</td>
                    <td>{{ track.release_date ?? '-' }}</td>
                    <td>{{ track.score ?? '-' }}</td>
                    <td>{{ track.spotify_track_id ?? '-' }}</td>
                    <td>{{ track.timestamp ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Track {
    title: string
    artist: string
    album: string | null
    timestamp: number | null
    release_date: string | null
    score: number | null
    spotify_track_id: string | null
}

interface AnalyzeResponse {
    source_url: string
    tracks: Track[]
}

const soundcloudUrl = ref<string>('')
const tracks = ref<Track[]>([])
const loading = ref<boolean>(false)
const error = ref<string | null>(null)

const analyzeMix = async (): Promise<void> => {
    error.value = null
    tracks.value = []
    loading.value = true

    try {
        const response = await fetch('/analyze-mix', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({
                soundcloud_url: soundcloudUrl.value,
            }),
        })

        if (!response.ok) {
            throw new Error(`Request failed (${response.status})`)
        }

        const data: AnalyzeResponse = await response.json()
        tracks.value = data.tracks ?? []
    } catch (e) {
        error.value =
            e instanceof Error ? e.message : 'Unknown error occurred'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.mix-analyzer {
    max-width: 900px;
    margin: 2rem auto;
    font-family: sans-serif;
}

form {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

input {
    flex: 1;
    padding: 0.5rem;
}

button {
    padding: 0.5rem 1rem;
    cursor: pointer;
}

.error {
    color: red;
    margin-bottom: 1rem;
}

.tracklist {
    width: 100%;
    border-collapse: collapse;
}

.tracklist th,
.tracklist td {
    border: 1px solid #ddd;
    padding: 0.5rem;
}

.tracklist th {
    background: #f5f5f5;
}
</style>
