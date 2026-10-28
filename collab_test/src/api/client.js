import axios from 'axios'

const TOKEN_KEY = 'rpd.sanctum.token'
let memoryToken = localStorage.getItem(TOKEN_KEY)
export const tokenStore = {
  get: () => memoryToken,
  set: (value) => { memoryToken = value || null; value ? localStorage.setItem(TOKEN_KEY, value) : localStorage.removeItem(TOKEN_KEY) },
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  timeout: 15000,
})

api.interceptors.request.use((config) => {
  const token = tokenStore.get()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use((response) => response, (error) => {
  if (error.response?.status === 401 && tokenStore.get()) {
    tokenStore.set(null)
    window.dispatchEvent(new Event('rpd:unauthorized'))
  }
  return Promise.reject(error)
})

const data = (request) => request.then((response) => response.data)
export const endpoints = {
  login: (credentials) => data(api.post('/auth/login', credentials)),
  logout: () => data(api.post('/auth/logout')),
  me: () => data(api.get('/auth/me')),
  stats: () => data(api.get('/dashboard/stats')),
  priority: () => data(api.get('/dashboard/priority-subjects')),
  bulletin: () => data(api.get('/bulletins/current')),
  subjects: (params) => data(api.get('/subjects', { params })),
  subject: (id) => data(api.get(`/subjects/${id}`)),
  createSubject: (body) => data(api.post('/subjects', body)),
  updateSubject: (id, body) => data(api.put(`/subjects/${id}`, body)),
  deleteSubject: (id) => data(api.delete(`/subjects/${id}`)),
  offenses: (subjectId, params) => data(api.get(subjectId ? `/subjects/${subjectId}/offenses` : '/offenses', { params })),
  addOffense: (id, body) => data(api.post(`/subjects/${id}/offenses`, body)),
  deleteOffense: (id) => data(api.delete(`/offenses/${id}`)),
  categories: () => data(api.get('/crime-categories')),
  notes: (id) => data(api.get(`/subjects/${id}/notes`)),
  addNote: (id, body) => data(api.post(`/subjects/${id}/notes`, body)),
  auditLogs: (params) => data(api.get('/audit-logs', { params })),
  exportSubjects: () => data(api.get('/subjects/export')),
}
