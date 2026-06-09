/**
 * Minimal team-balancing helper.
 *
 * Splits a set of players into two teams while keeping the average rating of
 * each side as close as possible. Players are sorted by rating (desc) and
 * dealt to whichever team currently has the lower total rating — a greedy
 * approximation that is good enough for a weekly pickup game.
 *
 * Pure logic, no API calls; the service layer (PlayerService) is responsible
 * for fetching players.
 */

export interface BalancePlayer {
  id: number
  nickname: string
  rating: number
}

export interface BalancedTeams {
  teamA: BalancePlayer[]
  teamB: BalancePlayer[]
}

export function useTeamBalance() {
  function balance(players: BalancePlayer[]): BalancedTeams {
    const ordered = [...players].sort((a, b) => b.rating - a.rating)

    const teamA: BalancePlayer[] = []
    const teamB: BalancePlayer[] = []
    let totalA = 0
    let totalB = 0

    for (const player of ordered) {
      if (totalA <= totalB) {
        teamA.push(player)
        totalA += player.rating
      } else {
        teamB.push(player)
        totalB += player.rating
      }
    }

    return { teamA, teamB }
  }

  return { balance }
}
