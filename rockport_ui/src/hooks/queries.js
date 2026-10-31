import { useQuery } from '@tanstack/react-query'
import { endpoints } from '../api/endpoints'

// Dashboard
export const useStats = () => useQuery({ queryKey: ['stats'], queryFn: endpoints.stats, refetchInterval: 30000 })
export const usePriority = () => useQuery({ queryKey: ['priority'], queryFn: endpoints.priority })
export const useBulletin = () => useQuery({ queryKey: ['bulletin'], queryFn: endpoints.bulletin, refetchInterval: 30000 })

// Only reports "offline" for network failures. A 401 or 403 is handled elsewhere.
export const useApiStatus = () =>
  useQuery({ queryKey: ['api-status'], queryFn: endpoints.me, refetchInterval: 30000, retry: false })

// Subjects
export const useSubjects = (params) =>
  useQuery({ queryKey: ['subjects', params], queryFn: () => endpoints.subjects(params), placeholderData: (previous) => previous })
export const useSubject = (id) => useQuery({ queryKey: ['subject', id], queryFn: () => endpoints.subject(id), enabled: Boolean(id) })
export const useRapSheet = (id) => useQuery({ queryKey: ['rap-sheet', id], queryFn: () => endpoints.rapSheet(id) })
export const useInfractionSummary = (id) => useQuery({ queryKey: ['infraction-summary', id], queryFn: () => endpoints.infractionSummary(id) })
export const useCostToState = (id) => useQuery({ queryKey: ['cost-to-state', id], queryFn: () => endpoints.costToState(id) })
export const useLocations = (id) => useQuery({ queryKey: ['locations', id], queryFn: () => endpoints.locations(id) })
export const useStatusHistory = (id, params) =>
  useQuery({ queryKey: ['status-history', id, params], queryFn: () => endpoints.statusHistory(id, params) })

// Infractions
export const useInfractionTypes = () => useQuery({ queryKey: ['infraction-types'], queryFn: endpoints.infractionTypes, staleTime: 3600000 })
export const useInfractions = (params) =>
  useQuery({ queryKey: ['infractions', params], queryFn: () => endpoints.infractions(params), placeholderData: (previous) => previous })
export const useSubjectInfractions = (id, params) =>
  useQuery({ queryKey: ['subject-infractions', id, params], queryFn: () => endpoints.subjectInfractions(id, params), placeholderData: (previous) => previous })

// Manhunts
export const useSubjectManhunts = (id, params) =>
  useQuery({ queryKey: ['subject-manhunts', id, params], queryFn: () => endpoints.subjectManhunts(id, params), placeholderData: (previous) => previous })
export const useManhunt = (id) => useQuery({ queryKey: ['manhunt', id], queryFn: () => endpoints.manhunt(id), enabled: Boolean(id) })

// Notes, rates, rankings, audit log
export const useNotes = (id, params) => useQuery({ queryKey: ['notes', id, params], queryFn: () => endpoints.notes(id, params) })
export const useCostRates = () => useQuery({ queryKey: ['cost-rates'], queryFn: endpoints.costRates, staleTime: 600000 })
export const useRankings = (params) =>
  useQuery({ queryKey: ['rankings', params], queryFn: () => endpoints.rankings(params), placeholderData: (previous) => previous })
export const useAuditLogs = (params) =>
  useQuery({ queryKey: ['audit-logs', params], queryFn: () => endpoints.auditLogs(params), placeholderData: (previous) => previous })

// A case file is a POST because every one generated is written to the audit log.
// gcTime 0 means opening the page again generates (and audits) a fresh one.
export const useCaseFile = (id) =>
  useQuery({ queryKey: ['case-file', id], queryFn: () => endpoints.caseFile(id), gcTime: 0, staleTime: 0, retry: false })
