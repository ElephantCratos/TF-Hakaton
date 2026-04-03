import { defineStore } from 'pinia'
import { base44 } from '../api/base44Client'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    courses: [] as any[],
    participants: [] as any[],
    groups: [] as any[],
    companies: [] as any[],
    specs: [] as any[],
    loading: false,
  }),

  actions: {
    async loadAll() {
      this.loading = true

      const [c, p, g, co, s] = await Promise.all([
        base44.entities.Course.list(),
        base44.entities.Participant.list(),
        base44.entities.TrainingGroup.list(),
        base44.entities.Company.list(),
        base44.entities.Specification.list(),
      ])

      this.courses = c
      this.participants = p
      this.groups = g
      this.companies = co
      this.specs = s

      this.loading = false
    }
  }
})