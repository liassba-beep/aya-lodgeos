# Segurança e operação

## Domínios

A produção deve servir a consola em `https://app.lodgesos.com`. No Cloudflare, o registo `A` de `app.lodgesos.com` deve apontar para `178.105.14.195`.

Para futuro multi-tenant por subdomínio, pode ser criado também `*.lodgesos.com -> 178.105.14.195`, mas a consola administrativa principal deve continuar em `app.lodgesos.com`.

Se o Cloudflare estiver em modo proxied, `dig` pode devolver IPs do Cloudflare em vez do IP da Hetzner. Nesse caso, confirmar no painel do Cloudflare que o alvo do registo `A` continua a ser `178.105.14.195`. Um `503` do Cloudflare normalmente significa que o domínio ainda não foi associado ao recurso correcto no Coolify, ou que o proxy do Coolify ainda não emitiu/ligou o certificado para esse hostname.

## HTTPS atrás do Coolify

O Coolify termina TLS no proxy e comunica com o container por HTTP interno. A aplicação confia nos cabeçalhos `X-Forwarded-*`, força `https` em produção e usa cookies de sessão seguros.

Variáveis recomendadas em produção:

```env
APP_URL=https://app.lodgesos.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

No Coolify, segredos como `APP_KEY`, `DB_*`, `REDIS_*`, `MAIL_*` e tokens externos devem estar marcados como variáveis apenas de runtime. Não devem ser variáveis de build.

## Painel `/admin`

O painel Filament em `/admin` tem uma allowlist de IP controlada por `ADMIN_IP_ALLOWLIST`.

Exemplos:

```env
ADMIN_IP_ALLOWLIST=197.235.10.20,102.68.0.0/16
```

Quando a lista está vazia, não há restrição de IP. Isto é útil em desenvolvimento e durante a primeira configuração. Em produção, preencher a lista com os IPs autorizados do Bachiro/equipa.

Pedidos de IPs fora da allowlist recebem `404`, para não revelar a existência do painel.

## Autenticação

As contas de administração devem usar palavra-passe forte e 2FA no Coolify. Para o AYA LodgeOS, a próxima etapa de segurança é tornar 2FA obrigatório dentro da própria aplicação para `super_admin`, `admin` e `proprietario`.

## Isolamento SaaS

O isolamento de dados é feito por `property_id`. O teste `TenantIsolationTest` garante que um proprietário não vê reservas de outro alojamento no recurso de Reservas.

O CI corre esse teste em todos os pull requests e pushes para `main`.

## Validação da Fase 0

Depois de o DNS propagar e o domínio ser configurado no Coolify:

```bash
dig app.lodgesos.com +short
curl -I https://app.lodgesos.com/up
curl -I https://app.lodgesos.com/admin
php artisan migrate:status
php artisan test --filter=TenantIsolationTest
```

Esperado: DNS a devolver `178.105.14.195` quando em modo DNS only, ou IPs do Cloudflare quando em modo proxied; `/up` com `200`, `/admin` a redireccionar para login ou devolver a página de login, migrações aplicadas e teste de isolamento verde.
