# Changelog

All notable changes to `pushery/sovereign-ai-for-laravel` are documented here. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

A release carries only the sections that have an entry, in this order: `### ⚠️ Breaking`, `### ✨ Added`, `### 🔧 Changed`, `### ⚡ Performance`, `### 🐛 Fixed`, `### 🔒 Security`, `### 🧹 Deprecated`, `### 🗑️ Removed` and `### 📚 Documentation`.

## [0.1.0] - 2026-11-20

### ✨ Added

- **IONOS AI Model Hub and OVHcloud AI Endpoints as providers of the Laravel AI SDK.** The package registers `ionos` and `ovhcloud` under `ai.providers`, each on a driver of its own name that builds the OpenAI-compatible provider of `laravel/ai`. The keys come from `SOVEREIGN_AI_IONOS_KEY` and `SOVEREIGN_AI_OVHCLOUD_KEY`, and the default models are `meta-llama/Llama-3.3-70B-Instruct` and `BAAI/bge-m3` at IONOS, `Mistral-Small-3.2-24B-Instruct-2506` and `bge-m3` at OVHcloud. A request keeps the timeout `laravel/ai` gives it, 60 seconds for an agent and 30 for embeddings unless the application names another, and `SOVEREIGN_AI_TIMEOUT` sets the wait of a request `laravel/ai` sends without one, such as the prompt that titles a remembered conversation.
- **Every request held to the endpoints of its cloud.** `https://openai.inference.de-txl.ionos.com/v1` for IONOS and `https://oai.endpoints.kepler.ai.cloud.ovh.net/v1` for OVHcloud. An entry of `ai.providers` with one of the two drivers and another URL stops the boot with `EndpointNotAllowed`, which names the provider and the value, and the gateway checks the URL again before every request. A redirect is not followed: an endpoint that answers with one ends the request in `ProviderRequestFailed`, which names the status.
- **A catalog of the models of both clouds with what each can do.** Structured output, tool calls and embeddings, as the documentation of each cloud states them, with the source of every entry. A request that needs a capability its model lacks is refused with `UnsupportedCapability` before it is sent, and so is a model the catalog does not name, unless the application names it under the catalog key of its cloud or switches the refusal off. An entry under that key the package cannot read, such as a capability by another name, stops the boot with `InvalidCatalog`, which names the cloud, the model and the value.
- **Errors that name the provider and the cause.** A rejected key, an unknown model, a refused request or any other error status, a 500 included, ends in `ProviderRequestFailed` with the status and the cloud's own message. A rate limit, exhausted credits or quota, an overload and a connection that is refused or times out keep the exceptions of `laravel/ai`, the four its failover between providers acts on, and the status 529 of an overloaded IONOS counts as an overload like a 503: tried once more after a `Retry-After` of up to ten seconds, and otherwise the same exception.
- **The tokens of a streamed answer.** A stream carries the input and output tokens of its cloud once it has ended, because the package asks both clouds for them with `stream_options`, which `laravel/ai` sends to an OpenAI-compatible provider only where its configuration names them.
- **A classification refused before it is sent.** Neither cloud offers one: IONOS serves no route for it, and OVHcloud serves the route for none of its models. A classification on `ionos` or `ovhcloud` ends in `UnsupportedCapability`, with a model as without one, and nothing is sent.

[Unreleased]: https://github.com/pushery/sovereign-ai-for-laravel/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/pushery/sovereign-ai-for-laravel/releases/tag/v0.1.0
