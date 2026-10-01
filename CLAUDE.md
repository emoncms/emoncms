# Writing style for AI contributors

This sets the language style for anything an AI assistant writes into this
repository: commit messages, pull request text, code comments, documentation
and settings templates.

Aim for simplicity and brevity.

## General

- British spelling in prose.
- US spelling in code and CSS identifiers: `color`, `center`.
- Plain declarative sentences.
- No em dashes. Use a comma, a full stop or brackets.

## Avoid

- Rhetorical contrast.
- Trailing explanatory clauses.
- Dramatic build.
- Code personification.
- Adverbs added for weight.
- Needless repetition.

Please avoid this style:

    The converter drops what its allowlist does not cover.

write instead:

    The converter drops items that are not covered by the allowlist.


Replace e.g: "The order it gets built in" with "Build order"

Please avoid using The or They at the start of comments, where a shorter form is adequate 

E.g "The body of a text widget" should be "Body of a text widget"

## Commit messages:

Please keep commit messages as short as possible, single line or bullet points, direct and factual no decorative words. Always write as a dry technical writer.
