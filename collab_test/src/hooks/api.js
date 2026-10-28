import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { endpoints } from '../api/client'
import { useToast } from '../context/ToastContext'

export const useSubjects = (params = {}) => useQuery({ queryKey: ['subjects', params], queryFn: () => endpoints.subjects(params) })
export const useApiStatus = () => useQuery({ queryKey: ['api-status'], queryFn: endpoints.me, refetchInterval: 30000, retry: false })
export const useSubject = (id) => useQuery({ queryKey: ['subject', id], queryFn: () => endpoints.subject(id), enabled: Boolean(id) })
export const useStats = () => useQuery({ queryKey: ['stats'], queryFn: endpoints.stats, refetchInterval: 30000 })
export const usePrioritySubjects = () => useQuery({ queryKey: ['priority'], queryFn: endpoints.priority })
export const useBulletin = () => useQuery({ queryKey: ['bulletin'], queryFn: endpoints.bulletin })
export const useOffenses = (subjectId, params = {}) => useQuery({ queryKey: ['offenses', subjectId, params], queryFn: () => endpoints.offenses(subjectId, params) })
export const useNotes = (subjectId) => useQuery({ queryKey: ['notes', subjectId], queryFn: () => endpoints.notes(subjectId), enabled: Boolean(subjectId) })
export const useCrimeCategories = () => useQuery({ queryKey: ['crime-categories'], queryFn: endpoints.categories, staleTime: 86400000 })
export const useAuditLogs = (params = {}) => useQuery({ queryKey: ['audit-logs', params], queryFn: () => endpoints.auditLogs(params) })

function useAction(action, success, invalidate = []) {
  const client = useQueryClient()
  const toast = useToast()
  return useMutation({ mutationFn: action, onSuccess: () => { invalidate.forEach((key) => client.invalidateQueries({ queryKey: key })); toast(success) } })
}
export const useCreateSubject = () => useAction(endpoints.createSubject, 'RECORD SAVED', [['subjects'], ['stats'], ['priority']])
export const useUpdateSubject = (id) => useAction((body) => endpoints.updateSubject(id, body), 'RECORD SAVED', [['subject', id], ['subjects'], ['stats'], ['priority']])
export const useDeleteSubject = () => useAction(endpoints.deleteSubject, 'RECORD ARCHIVED', [['subjects'], ['stats'], ['priority']])
export const useAddOffense = (id) => useAction((body) => endpoints.addOffense(id, body), 'OFFENSE ADDED', [['subject', id], ['offenses'], ['stats'], ['priority']])
export const useAddNote = (id) => useAction((body) => endpoints.addNote(id, body), 'NOTE ADDED', [['notes', id]])
