import { api } from './client'

const get = (url, params) => api.get(url, { params }).then((response) => response.data)
const post = (url, body) => api.post(url, body).then((response) => response.data)
const put = (url, body) => api.put(url, body).then((response) => response.data)
const patch = (url, body) => api.patch(url, body).then((response) => response.data)
const remove = (url) => api.delete(url).then((response) => response.data)

export const endpoints = {
  // auth
  login: (body) => post('/auth/login', body),
  logout: () => post('/auth/logout'),
  me: () => get('/auth/me'),

  // dashboard
  stats: () => get('/dashboard/stats'),
  priority: () => get('/dashboard/priority'),
  bulletin: () => get('/bulletins/current'),

  // subjects
  subjects: (params) => get('/subjects', params),
  subject: (id) => get(`/subjects/${id}`),
  createSubject: (body) => post('/subjects', body),
  updateSubject: (id, body) => put(`/subjects/${id}`, body),
  archiveSubject: (id) => remove(`/subjects/${id}`),
  changeStatus: (id, body) => post(`/subjects/${id}/status`, body),
  threatOverride: (id, body) => put(`/subjects/${id}/threat-override`, body),
  exportSubjects: () => api.get('/subjects/export', { responseType: 'blob' }).then((response) => response.data),

  // record screens
  rapSheet: (id) => get(`/subjects/${id}/rap-sheet`),
  infractionSummary: (id) => get(`/subjects/${id}/infraction-summary`),
  costToState: (id) => get(`/subjects/${id}/cost-to-state`),
  locations: (id) => get(`/subjects/${id}/locations`),
  statusHistory: (id, params) => get(`/subjects/${id}/status-history`, params),
  caseFile: (id) => post(`/subjects/${id}/case-file`),

  // infractions
  infractionTypes: () => get('/infraction-types'),
  infractions: (params) => get('/infractions', params),
  subjectInfractions: (id, params) => get(`/subjects/${id}/infractions`, params),
  addInfraction: (id, body) => post(`/subjects/${id}/infractions`, body),
  updateInfraction: (id, body) => patch(`/infractions/${id}`, body),
  deleteInfraction: (id) => remove(`/infractions/${id}`),

  // manhunts
  subjectManhunts: (id, params) => get(`/subjects/${id}/manhunts`, params),
  manhunt: (id) => get(`/manhunts/${id}`),
  logManhunt: (id, body) => post(`/subjects/${id}/manhunts`, body),
  deleteManhunt: (id) => remove(`/manhunts/${id}`),

  // sightings and notes
  addSighting: (id, body) => post(`/subjects/${id}/sightings`, body),
  notes: (id, params) => get(`/subjects/${id}/notes`, params),
  addNote: (id, body) => post(`/subjects/${id}/notes`, body),
  deleteNote: (id) => remove(`/notes/${id}`),

  // reference data and reports
  costRates: () => get('/cost-rates'),
  rankings: (params) => get('/rankings', params),
  auditLogs: (params) => get('/audit-logs', params),
}
