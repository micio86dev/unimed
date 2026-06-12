# syntax=docker/dockerfile:1
FROM node:24-alpine

# Pin pnpm to match the workspace (avoids pnpm 10 writing stray workspace files
# into the bind-mounted source).
RUN corepack enable && corepack prepare pnpm@9.15.9 --activate

WORKDIR /app

# Source is bind-mounted at runtime; dependencies install on first boot into an
# anonymous volume so they don't clobber the host's node_modules.
EXPOSE 3000

CMD ["sh", "-c", "pnpm install && pnpm dev --host 0.0.0.0"]
