# Segurança e operação

## Domínios

A produção deve servir a consola em `https://app.lodgesos.com`. No Cloudflare, o registo `A` de `app.lodgesos.com` deve apontar para `178.105.14.195`.

Para multi-tenant por subdomínio, `*.lodgesos.com -> 178.105.14.195` pode apontar para o mesmo servidor. A consola master fica em `app.lodgesos.com`; cada tenant deve usar o seu próprio subdomínio, por exemplo `mikaya.lodgesos.com/admin`.

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

## Painéis `/admin`

O painel master em `/admin`, quando servido no domínio central, tem uma allowlist de IP controlada por `ADMIN_IP_ALLOWLIST`.

Exemplos:

```env
ADMIN_IP_ALLOWLIST=197.235.10.20,102.68.0.0/16
```

Quando a lista está vazia, não há restrição de IP. Isto é útil em desenvolvimento e durante a primeira configuração. Em produção, preencher a lista com os IPs autorizados do Bachiro/equipa. A allowlist não bloqueia os subdomínios dos tenants, porque os proprietários acedem pelo painel da sua própria propriedade.

Pedidos de IPs fora da allowlist recebem `404`, para não revelar a existência do painel.

O domínio central `app.lodgesos.com` é reservado a `super_admin` e `admin` master. Utilizadores de tenants, como proprietários, gerentes e trabalhadores, não devem entrar no domínio central. Devem aceder ao painel pelo subdomínio do tenant, como `https://mikaya.lodgesos.com/admin`.

Rotas auxiliares como PDFs, CSVs, recibos, etiquetas QR, calendário e endpoints mobile usam o middleware `module:{modulo},{accao}`. Isto impede que um utilizador autenticado aceda a dados só por conhecer o URL.

## Autenticação

As contas de administração devem usar palavra-passe forte e 2FA no Coolify. O AYA LodgeOS já tem campos internos para 2FA e a flag:

```env
REQUIRE_SENSITIVE_2FA=true
```

Quando activa, esta flag exige 2FA confirmado para `super_admin`, `admin` e `proprietario`. Só deve ser ligada depois de os utilizadores sensíveis terem 2FA configurado.

O registo público de contas fica fechado por defeito:

```env
ALLOW_PUBLIC_REGISTRATION=false
```

Contas de tenants devem ser criadas pelo master ou por proprietários com permissão no módulo `Equipa e acessos`.

Sessões web expiram por inactividade no servidor:

```env
WEB_IDLE_TIMEOUT_MINUTES=30
```

Quando o limite é ultrapassado, o AYA LodgeOS invalida a sessão, regista `session_timeout` na auditoria e obriga o utilizador a iniciar sessão novamente. Isto aplica-se ao painel master, aos painéis dos tenants e à app web autenticada.

Eventos de acesso auditados:

- `login`: entrada web.
- `logout`: saída web.
- `session_timeout`: sessão expirada por inactividade.
- `worker_login`: entrada na app mobile de trabalhador.
- `worker_logout`: saída da app mobile de trabalhador.

No painel master, a auditoria mostra o tenant e o alojamento quando o evento pertence a uma propriedade.

## Isolamento SaaS

O isolamento de dados é feito por `property_id`. O `TenantContext` não escolhe automaticamente uma propriedade quando não há utilizador autenticado. Páginas públicas resolvem o tenant pelo slug/domínio; áreas autenticadas resolvem pelo utilizador.

O teste `TenantIsolationTest` garante que um proprietário não vê reservas de outro alojamento no recurso de Reservas.

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
