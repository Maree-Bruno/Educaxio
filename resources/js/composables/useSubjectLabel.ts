export function resolveSubjectLabel(subjectName: string, lmLevel: number | null | undefined): string {
    return lmLevel ? `LM${lmLevel} – ${subjectName}` : subjectName;
}