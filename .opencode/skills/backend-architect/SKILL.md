---
name: backend-architect
description: Skill obrigatoria do backend (control-school-back - Laravel 11 API-only). Leia SEMPRE antes de modificar qualquer rota, controller, request, action, model, migration ou resource. Define o contrato de endpoint, as camadas (Controller > Action > Model > Resource), validacao via FormRequest, formato de resposta e o checklist de seguranca/performance.
---

# Backend Architect

## Contrato de endpoint (preencher ANTES de codar)

| Campo | Definicao |
| --- | --- |
| Metodo + Rota | ex.: POST /api/schools/{school}/naps |
| Payload (Request) | campos + tipos + regras (FormRequest) |
| Retorno (Response) | Resource + status (201/200/204/4xx/5xx) |
| Regras de negocio | o que NAO pode acontecer e onde valida |
| Side effects | outras tabelas, cache, events |

## Fluxo em camadas (obrigatorio)

routes/api.php -> FormRequest (validacao) -> Controller (magro, sem regra) -> Action/Service (regra de negocio) -> Model (Eloquent) -> Resource (resposta padronizada).

- Nunca colocar regra de negocio dentro de Controller. Sempre em Action/Service.
- Mass assignment protegido ($fillable, nunca $guarded = []).
- Validacao SEMPRE em FormRequest (nunca dentro do controller).
- Respostas: 201 (criacao), 204 (sem corpo), 422 (validacao/regra), 404 (nao encontrado).

## Checklist seguranca & performance (aplicar sempre)

- auth:sanctum + policy/role para escola/NAP (nao confiar so no middleware de rota).
- Eager load (with/withCount) para evitar N+1 em listas.
- Indices nas FKs/pivots (user_schools, etc).
- Nunca logar senha/token; usar Hash::make / bcrypt.
- Testes de feature (tests/Feature) por fluxo alterado.

## Pontos do dominio (NAP x acesso)

- professor / coordenador: NAP fixo (needsFixedNap = true; sem NAP fixo = erro).
- orientador / diretor: acesso FULL (sem NAP fixo; veem/mexem em todos os NAPs).
- diretor = mesmo acesso de professor, porem em TODOS os NAPs (sem NAP fixo).
- orientador = acesso full/orientador (visao completa da escola).
- LIMITE de NAPs por escola: NapCapacity::LIMIT = 2.
