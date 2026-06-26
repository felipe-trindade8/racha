# Soccer Management System

System to manage weekly soccer matches for a group of friends.

## Objectives

- Control group members
- Financial control with monthly and occasional expenses and payments
- Cash flow control based on finances
- Control of member attendance at matches
- Manage player rosters
- Balanced team division
- Score recording
- Front-end mobile first

## Business Context

A group of approximately 20 to 30 players meets weekly on saturdays to play soccer in a fixed place.
Each player pays a monthly fee that covers field rental, equipment and other expenses.
Before each match, players confirm attendance.
Based on attended players, balanced teams are generated.
Substitutions ocurr in the half time, keeping the teams balanced.
A scale of players are used to substitute players.
Substitute players are ordered in a substitution queue based on .
At half time, players leave and enter the match following the queue order.
The system must keep track of who has already played and prioritize players with less play time.
After the match, the score and statistics are recorded.

## Users

- Administrator
- Player

## MVP Functionalities

### Attendance

- Confirm attendance
- Confirmation list
- Attendance status (available/injured)

### Finances

- Monthly payment
- Cash flow
- Expenses

### Matches

- Match creation
- Result history
- Match score

### Player profile

- Create player
- Update player
- Inactivate player
- Player positions

## Future Functionalities

### Matches

- Split similar teams
- Control interval substitutions
- Automatic substitution

### Team Balancing

Generate balanced teams based on:

- Player rating (1-5)
- Preferred positions
- Attendance confirmation
  The system should attempt to keep the average rating of both teams as close as possible.

### Reports

- Financial
- Matches
- Players

## Entities

### User

Represent s user that can access the plataform.

- Id
- Email
- Password
- Role
- PlayerId

### Player

Represents a member of the group.

- Id
- Name
- Nickname
- Rating
- Status
- Positions
- Phone

### GameMatch

Represents played and planned matches. Named `GameMatch` (table
`game_matches`) because `Match` is a reserved keyword in PHP 8+.

- Id
- Date
- Team A Id
- Team B Id
- Status

### GameMatchTeam

Represents teams that play matches. There should be only 2 per match.

- Id
- GameMatch Id
- Team Name
- Result

### TeamPlayer

Represents relation of player and team.

- Id
- GameMatchTeam Id
- Player Id
- Position
- Game Rating
- IsStarter

### Substitution

Represents substitutions that might happen in game.

- Id
- GameMatchTeam Id
- Player Id In
- Player Id Out
- Match Time (minute)

### Attendance

Represents attendance list with status for better plan.

- Id
- Player Id
- GameMatch Id
- Status

### FinancialTransaction

Represents any money movement in the group.

- Id
- Player Id (optional)
- Description
- Amount
- Type (income/expense)
- Date
- Status (open/paid)

Business rules:

- A transaction's details (player, description, amount, type, date) can only
  be edited while it is `open`. A `paid` transaction is locked and must be
  reopened (status back to `open`) before any further edit.
- Cash flow reports `income`, `expense` and `balance = income − expense`. By
  default it considers only `paid` transactions (realized money), so the
  default balance is `paid income − paid expense`. The cash-flow endpoint
  accepts a `status` filter (`paid` default, `open`, or `all`) to also report
  pending or combined movements, and an optional `from`/`to` month range.
