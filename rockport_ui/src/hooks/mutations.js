import { useMutation, useQueryClient } from '@tanstack/react-query'
import { endpoints } from '../api/endpoints'
import { useToast } from '../context/toast'

function useAction(mutationFn, message, keys) {
  const client = useQueryClient()
  const toast = useToast()

  return useMutation({
    mutationFn,
    onSuccess: () => {
      keys.forEach((queryKey) => client.invalidateQueries({ queryKey }))
      if (message) toast(message)
    },
  })
}

// Everything on screen that depends on one subject's numbers.
const subjectKeys = (id) => [
  ['subject', id],
  ['rap-sheet', id],
  ['infraction-summary', id],
  ['cost-to-state', id],
  ['subject-infractions', id],
  ['subject-manhunts', id],
  ['status-history', id],
  ['locations', id],
  ['manhunt'],
  ['subjects'],
  ['stats'],
  ['priority'],
  ['rankings'],
  ['infractions'],
]

export const useCreateSubject = () => useAction(endpoints.createSubject, 'RECORD CREATED', [['subjects'], ['stats'], ['priority']])
export const useUpdateSubject = (id) => useAction((body) => endpoints.updateSubject(id, body), 'RECORD SAVED', subjectKeys(id))
export const useArchiveSubject = () => useAction(endpoints.archiveSubject, 'RECORD ARCHIVED', [['subjects'], ['stats'], ['priority'], ['rankings']])
export const useChangeStatus = (id) => useAction((body) => endpoints.changeStatus(id, body), 'STATUS UPDATED', subjectKeys(id))
export const useThreatOverride = (id) => useAction((body) => endpoints.threatOverride(id, body), 'THREAT LEVEL UPDATED', subjectKeys(id))

export const useAddInfraction = (id) => useAction((body) => endpoints.addInfraction(id, body), 'INFRACTION RECORDED', subjectKeys(id))
export const useUpdateInfraction = (subjectId) =>
  useAction(({ id, body }) => endpoints.updateInfraction(id, body), 'INFRACTION UPDATED', subjectKeys(subjectId))
export const useDeleteInfraction = (subjectId) => useAction(endpoints.deleteInfraction, 'INFRACTION REMOVED', subjectKeys(subjectId))

export const useLogManhunt = (id) => useAction((body) => endpoints.logManhunt(id, body), 'MANHUNT LOGGED', subjectKeys(id))
export const useDeleteManhunt = (subjectId) => useAction(endpoints.deleteManhunt, 'MANHUNT REMOVED', subjectKeys(subjectId))

export const useAddSighting = (id) => useAction((body) => endpoints.addSighting(id, body), 'SIGHTING LOGGED', [['locations', id], ['subject', id], ['subjects']])
export const useAddNote = (id) => useAction((body) => endpoints.addNote(id, body), 'NOTE ADDED', [['notes', id]])
export const useDeleteNote = (subjectId) => useAction(endpoints.deleteNote, 'NOTE DELETED', [['notes', subjectId]])
