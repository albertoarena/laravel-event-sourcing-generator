# Workflow

> How work gets done in this repo: authoring plans, meeting documentation requirements, and daily task tracking. Extracted from `CLAUDE.md`; load this when writing a plan or logging work.

## Plans & Claude Documentation

### Storage
- Plans: `docs/plans/`
- Completed plans: `docs/plans/completed/`
- Daily "done" logs: `.claude/done/`
- Priming documents (when warranted): `.claude/priming/`

### Plan File Format
- Format: `YYYY-MM-DD-<short-description>.md`
- Example: `2026-01-26-add-aggregate-validation.md`
- Include: date, purpose, and relevant context
- Keep changelog at top with dates and descriptions of changes
- Reference plans with links to specific files
- Add feedback in separate section at bottom

### Plan Structure
- **Title:** Clear, descriptive title
- **Changelog:** List of changes with dates
- **Purpose:** Brief summary of the plan's goal
- **Steps:** Detailed, step-by-step instructions for implementation
- **References:** Links to related documentation, code, or resources
- **Feedback:** Section for reviewers to provide comments or suggestions

### Writing Effective Plans
- Be specific about what needs to be done
- Provide context for why the task is necessary
- For complex tasks, break down steps into smaller sub-steps for clarity
- Use bullet points or numbered lists for easy readability
- Time estimates are optional in plan documents; if included, note AI-assisted vs traditional development ranges

### Plan Review (Self-Check)
**IMPORTANT:** After writing a plan, always re-read and review it before presenting to the user. Check for:
- **Clarity:** Are the steps clear and unambiguous?
- **Completeness:** Are any steps or edge cases missing?
- **Maintainability:** Will this approach be easy to maintain long-term?
- **Context:** Is there enough context for an AI assistant to implement this?
- **Improvements:** Are there areas that could be simplified or improved?

If issues are found during self-review, update the plan before asking for approval.

### Management Summary (Required for Major Features)
Significant features or systems that require stakeholder buy-in MUST include a **Management Summary** section immediately after the metadata (date, author, status). For minor tasks or bug fixes, this section can be omitted.

This summary is for non-technical stakeholders and should answer:

1. **What is this?** - One paragraph explaining the feature/system in plain language
2. **Why do we need it?** - Business benefits (bullet points)
3. **Where/How is it used?** - Who benefits and how they use it
4. **What does it enable?** - Key capabilities or example use cases
5. **Investment** - Effort estimate if needed for stakeholder planning (optional), infrastructure costs if applicable
6. **Timeline** - High-level phases with durations
7. **Risks & Mitigations** - Top 2-3 risks with how they're addressed

**Guidelines:**
- Use simple, non-technical language
- Keep it to one page maximum
- Use tables for comparisons and timelines
- Avoid jargon - explain technical terms if necessary
- Focus on business value, not implementation details

### Plan Completion
**IMPORTANT:** When a plan's implementation is fully completed:
1. Move the plan file from `docs/plans/` to `docs/plans/completed/`
2. Update the plan's changelog with the completion date
3. If the system/feature warrants it, create a priming document in `.claude/priming/`

A plan is considered complete when all implementation tasks are done and tested, not when it's just been written.

### Documentation Requirements
- Re-read `CLAUDE.md` after completing each task to keep instructions primed in context
- Check for `.claude/overrides.md` for any project-specific additions to these guidelines

## Task Tracking

### Daily Updates
- After completing each task, immediately update the daily done file:
- `.claude/done/YYYY-MM-DD-done.md`
- Include a summary of work done that day
- Use bullet points for each completed task with timestamps
- Provide detailed explanations for significant changes below the summary
- Update the summary section after each addition to reflect the day's work
- Example file structure:
  ```
  .claude/
      done/
          2024-06-01-done.md
          2024-06-02-done.md
  ```

### Guidelines
- Add a bullet to "Done Today" for each change with timestamp
- Create a detailed section below for each significant change
- Keep "Done Today" as a quick overview; details go in sections below
- After adding a new item to "Done Today", rewrite the Summary section to reflect the updated work
- A brief description (max 250 tokens total for the entire Summary section) covering any other work done that day. Written at the end of the day or when asked.
