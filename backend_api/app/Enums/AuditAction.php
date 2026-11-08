<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case Viewed = 'viewed';
    case Created = 'created';
    case Updated = 'updated';
    case Archived = 'archived';
    case Restored = 'restored';
    case Exported = 'exported';
    case StatusChanged = 'status_changed';
    case ThreatOverridden = 'threat_overridden';
    case NoteAdded = 'note_added';
    case NoteDeleted = 'note_deleted';
    case InfractionAdded = 'infraction_added';
    case InfractionServed = 'infraction_served';
    case InfractionDeleted = 'infraction_deleted';
    case ManhuntLogged = 'manhunt_logged';
    case ManhuntDeleted = 'manhunt_deleted';
    case SightingAdded = 'sighting_added';
    case RatesUpdated = 'rates_updated';
    case CaseFileGenerated = 'case_file_generated';
    case AccessDenied = 'access_denied';
    case AccessRequested = 'access_requested';
    case AccessApproved = 'access_approved';
    case AccessRejected = 'access_rejected';
    case PersonnelCreated = 'personnel_created';
    case PersonnelUpdated = 'personnel_updated';
    case PasswordIssued = 'password_issued';
    case PersonnelDeactivated = 'personnel_deactivated';
    case PersonnelActivated = 'personnel_activated';
    case ReportFiled = 'report_filed';
    case ReportUpdated = 'report_updated';
    case ReportReviewed = 'report_reviewed';
    case ReportDeleted = 'report_deleted';
}
