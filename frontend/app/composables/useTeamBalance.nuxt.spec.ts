import { describe, it, expect } from 'vitest'
import { useTeamBalance, type BalancePlayer } from './useTeamBalance'

const players: BalancePlayer[] = [
  { id: 1, nickname: 'Ana10', rating: 5 },
  { id: 2, nickname: 'Bia', rating: 4 },
  { id: 3, nickname: 'Caio', rating: 3 },
  { id: 4, nickname: 'Duda', rating: 2 },
]

describe('useTeamBalance', () => {
  it('places every player on exactly one team', () => {
    const { balance } = useTeamBalance()
    const { teamA, teamB } = balance(players)

    const ids = [...teamA, ...teamB].map((p) => p.id).sort()
    expect(ids).toEqual([1, 2, 3, 4])
  })

  it('keeps the two teams rating-balanced', () => {
    const { balance } = useTeamBalance()
    const { teamA, teamB } = balance(players)

    const total = (team: BalancePlayer[]) =>
      team.reduce((sum, p) => sum + p.rating, 0)

    expect(total(teamA)).toBe(total(teamB))
  })

  it('does not mutate the input array', () => {
    const { balance } = useTeamBalance()
    const input = [...players]
    balance(input)

    expect(input).toEqual(players)
  })
})
