# Claude Development Guidelines

## Core Principles

### Efficiency First
- **Minimize unnecessary output** - no preambles, summaries, or explanations unless requested
- **Direct answers** - get straight to the point without elaboration
- **Concise responses** - keep text under 4 lines when possible
- Answer with one word if appropriate. No introductions or conclusions.

### Code Quality
- **Never add comments** unless code is complex or explicitly requested
- **No unnecessary docstrings** - only add if truly needed
- **Follow existing patterns** - understand the codebase conventions before changing anything
- **Minimal changes** - only modify what's necessary for the task
- **No premature abstractions** - one-time operations don't need helpers

### Understanding Before Acting
- **Read files first** - never propose changes without reviewing the code
- **Understand context** - check imports, existing libraries, and patterns
- **Research dependencies** - verify libraries exist in package.json/composer.json/pom.xml before using them
- **No assumptions** - gather information before concluding root causes

## Workflow

### 1. Investigation Phase
1. Read the relevant files to understand current implementation
2. Check dependencies and available libraries
3. Understand code conventions and style
4. Identify all locations that need changes

### 2. Planning Phase
- For non-trivial tasks, break down into smaller steps
- Consider which files need modification
- Check for references and dependencies
- Don't skip verification steps

### 3. Implementation Phase
- Make changes aligned with existing patterns
- Use libraries already in the project
- Keep modifications focused and minimal
- No breaking changes without explanation

### 4. Verification Phase
- **Always run linters** - npm/composer/maven lint commands
- **Check for errors** - don't assume the code works
- **Verify all locations** - ensure all related files were updated

## Code Style Guidelines

### Universal Best Practices
- Follow the code style already used in the project
- Maintain consistent naming conventions
- Respect existing architectural patterns
- Use framework defaults when available

### Language-Specific
- **Next.js/React**: Use existing component patterns, respect hooks conventions
- **Laravel**: Follow Laravel structure, use existing service/repository patterns
- **PHP**: Use namespace conventions, follow PSR standards if present

## Handling External Services & APIs
- **Ask first** - request API keys before implementing integrations
- **Check documentation** - verify current API patterns via web search
- **Use package versions** - trust package.json over knowledge cutoff
- **Error handling** - implement proper error handling for external calls
- **Never hardcode** - use environment variables for configuration

## Security

### Critical Rules
- **Never log secrets or keys** - check for leaked credentials
- **Never commit secrets** - always use environment variables
- **Validate input** - at system boundaries (user input, external APIs)
- **Follow best practices** - implement security standards for your framework
- **never run artisan commands** - don't run artisan commands then altered the database, only the user can do that

### No Over-Engineering
- Only validate at boundaries (external input)
- Trust internal code and framework guarantees
- Don't add error handling for impossible scenarios
- Don't implement features that aren't needed

## Git & Version Control

### Commits
- Create NEW commits (don't amend previous commits)
- Write clear commit messages explaining the "why"
- Only commit when explicitly asked
- Never force push without user approval

### Before Pushing
- Verify all changes are correct
- Ensure tests pass
- Check linters pass
- Review git diff carefully

## Project Structure Understanding

Before starting work:
1. Identify the main technology stack
2. Understand project architecture
3. Check for existing configuration files (.env, config files)
4. Review recent commits for patterns
5. Check README for setup/build instructions



## What NOT to Do

❌ Use feature flags for code that should be different
❌ Add unused imports or exports
❌ Rename `_vars` or add "removed" comments
❌ Force multiple small commits when one bundled commit makes sense
❌ Use `git add .` - always specify exact files
❌ Skip hooks or bypass signing
❌ Assume library availability - verify first
❌ Downgrade packages without reason
❌ Make less valuable fixes indefinitely

## Handling Blockers

When stuck:
1. Gather more information (don't retry same action)
2. Try alternative approaches
3. Use web search for latest solutions
4. Ask user for clarification
5. Provide interim solution if full solution blocked

## Performance Considerations

- Minimize redundant operations
- Cache results when appropriate
- Use efficient algorithms
- Don't over-optimize prematurely
- Profile before optimizing

## Documentation

- No README updates unless explicitly requested
- No documentation files unless explicitly requested
- Code should be self-explanatory
- Comments only for non-obvious logic

## Final Checklist Before Completion

- [ ] All requested changes implemented
- [ ] Code follows project conventions
- [ ] Linters pass (npm run lint / composer lint / etc)
- [ ] Tests pass (if project has tests)
- [ ] No console errors or warnings
- [ ] Security best practices followed
- [ ] No secrets in code
- [ ] Dependencies verified in package/lock files
- [ ] Git diff reviewed
- [ ] Task verified complete
