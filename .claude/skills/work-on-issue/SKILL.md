# Work On Issue

Use this skill when implementing a GitHub issue.

## Assumption

Project context has already been loaded.

## Phase 1 - Analysis

Read and analyze the issue.

Identify:

- Requirements
- Business rules
- Acceptance criteria

Determine:

### Impacted Domain

- Entities
- Relationships

### Database Impact

- Tables
- Columns
- Migrations

### Backend Impact

- Controllers
- Services
- Requests
- Policies
- Models

### API Impact

- Endpoints
- Contracts

### Frontend Impact

- Pages
- Components
- Composables
- Services
- Types

### Testing Impact

- Backend tests
- Frontend tests
- E2E tests

## Phase 2 - Implementation Plan

Create a concise implementation plan.

Present:

- Tasks
- Files expected to change
- Tests to be created

Then stop and wait for approval.

## Phase 3 - Git Rules

For every issue:

- Follow coding-standards.md
- Create a new branch before implementation

## Phase 4 - Implementation

Only execute after explicit approval.

Requirements:

- Follow architecture.md
- Follow coding-standards.md
- Follow existing project patterns
- Keep changes focused on the issue scope

During implementation:

- Use Laravel-related skills for backend changes
- Use Nuxt-related skills for frontend changes
- Use Pest-related skills for backend tests
- Use Vitest-related skills for frontend tests
- Use Playwright-related skills for E2E tests

## Phase 5 - Testing

Create or update automated tests.

Run relevant tests.

Report:

- Tests executed
- Results
- Failures if any

## Phase 6 - Documentation

Update documentation only if the change affects:

- Public APIs
- User workflows
- Architecture decisions

## Phase 7 - Summary

Provide:

### Completed Work

What was implemented.

### Files Changed

High-level summary.

### Tests

What was tested.

### Notes

Any follow-up recommendations or technical debt identified.

## Phase 8 - Git Workflow

After implementation is completed and all tests pass:

## Commit

1. Review changed files
2. Create a commit using Conventional Commits

Commit format:

<type>(<scope>): <description>

Examples:

- feat(auth): add password reset endpoint
- fix(users): validate email uniqueness
- refactor(tournament): simplify ranking calculation

## Pull Request

1. Push branch to origin
2. Create a Pull Request against the target branch

PR title:

<issue-title>

PR description:

### Summary

Brief description of the implementation.

### Changes

- Change 1
- Change 2
- Change 3

### Tests

- [x] Backend tests
- [x] Frontend tests
- [x] E2E tests (if applicable)

### Screenshots

If UI changes were made.

### Checklist

- [x] Requirements implemented
- [x] Tests passing
- [x] Coding standards followed
- [x] No unrelated changes included

## Final Output

Provide:

- Branch name
- Commit hash
- Commit message
- Pull Request URL
- Any follow-up recommendations
