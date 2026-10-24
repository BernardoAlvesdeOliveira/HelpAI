# HelpAI

Automated restaurant order support system via WhatsApp. The project combines a Laravel application, Filament admin panel, Evolution API, n8n, Redis, and Google Gemini.

## Overview

Main flow:

1. The customer sends a message through WhatsApp.
2. Evolution API forwards the event to the Laravel webhook.
3. Laravel validates and persists the message.
4. Laravel sends the message to the n8n webhook.
5. n8n batches messages, checks the menu, and processes the conversation with Google Gemini.
6. The response is sent back to the customer through Evolution API.
7. n8n persists the response in Laravel.
8. After order confirmation, n8n sends the data to the order webhook.
9. Laravel creates the order and updates the admin panel in real time.

## n8n Automation

Main file:

```text
n8n/workflow.json
```

Workflow: `automação`.

![n8n Workflow](public/images/workflow.png)

### Sequence

1. `Webhook` receives the message.
2. `Datas` normalizes name, text, type, IDs, phone, instance, and timestamp.
3. `HTTP Request1` checks the conversation state in Laravel.
4. `If1` decides whether the message continues in the flow.
5. `Redis1` batches messages by phone number.
6. `Wait` delays 15 seconds to gather nearby messages.
7. `Redis` retrieves the `Messages` queue.
8. `IsSameMessage?` ensures only the latest message moves forward.
9. `Mark Message As Read` marks the message in Evolution API.
10. `AI Agent` interprets the conversation and handles support.
11. `Google Gemini Chat Model` provides the language model.
12. `Redis Chat Memory` keeps per-conversation context.
13. `get_all_menu_items` and `get_available_menu_items` query Laravel.
14. `Code in JavaScript` normalizes response, order, delivery, payment, total, and status.
15. `Send Text` sends the response to the customer.
16. `HTTP Request4` saves the response in Laravel.
17. `Redis2` clears the phone queue.
18. `If` checks `ConversationFinished`.
19. `Redis3` clears temporary conversation memory.
20. `HTTP Request` sends the completed order to `/api/webhook/order`.

### Agent rules

The agent serves in Brazilian Portuguese and handles:

- Menu item selection
- Quantity and price
- Meal boxes and drinks
- Delivery or pickup
- Delivery address
- Payment timing
- Order confirmation
- Final total

`ConversationFinished` should only be `true` after explicit customer confirmation and all required data is filled.

### Credentials

The workflow references n8n credentials for:

- Google Gemini
- Redis
- Evolution API

The file contains credential names and IDs, not secrets. Configure credentials in n8n and do not publish keys in the repository.

### Operational notes

- The workflow has internal URLs configured directly in some nodes. Adjust them per environment.
- The file's `pinData` may contain execution payloads and WhatsApp data. Review this data before sharing the workflow.
- Laravel must be reachable by n8n.
- Redis must be shared or correctly addressed between Laravel and n8n when the application depends on the same state.
- The workflow uses a 15-second wait to group consecutive messages.

## Stacks

### Backend

- PHP `^8.3`
- Laravel `^13.17`
- Livewire `^4.1`
- Filament `^5.9`
- Laravel Fortify `^1.37.2`
- Laravel Sanctum `^4.0`
- Laravel Reverb `^1.0`
- Pusher PHP `^7.3`

### Frontend

- Node.js and npm
- Vite `^8.0.0`
- Vite Plus `0.3.0`
- Tailwind CSS `^4.0.7`
- Laravel Vite Plugin `^3.1`
- Laravel Echo `^2.5.0`
- Pusher JS `^8.6.0`
- Blade, Alpine.js, and Livewire

### Quality

- PHPUnit `^12.5.23`
- Larastan `^3.9`
- Laravel Pint `^1.27`

### Infrastructure services

- MySQL or SQLite for development
- PostgreSQL for the n8n environment
- Redis for cache, queues, batching, and conversational memory
- Evolution API for WhatsApp integration
- n8n for automation
- Google Gemini for language processing and audio transcription
- Laravel Reverb/Pusher for real-time events

## Project structure

```text
app/
├── Actions/              Authentication actions and business rules
├── Events/               Broadcast events, including order creation
├── Filament/Resources/   Admin panel CRUD resources
├── Http/Controllers/     Webhooks, conversations, and menu
├── Models/               User, Orders, Menu, and Whatsapp models
├── Providers/            Application and Filament configuration
└── Services/             Evolution API, orders, and conversation state

database/
├── factories/            Test factories
├── migrations/           Database schema
└── seeders/              Seed data

resources/
├── css/                  Styles
├── js/                   JavaScript, Echo, and passkeys
└── views/                Blade views and panel screens

routes/
├── api.php               API endpoints and webhooks
├── channels.php          Broadcast channels
├── settings.php          Settings routes
└── web.php               Web and panel routes

n8n/
├── docker-compose.yml    n8n, Evolution API, Redis, and PostgreSQL services
└── workflow.json         Automated support workflow
```

## Requirements

- PHP `8.3` or higher
- Composer
- Node.js and npm
- MySQL or SQLite
- Redis
- Docker and Docker Compose, if automation services run locally

## Installation

The setup command installs dependencies, creates the environment, generates the app key, runs migrations, and builds assets:

```bash
composer run setup
```

Manual installation:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Configure the `.env` file before starting the application. Never version `.env` or external service credentials.

## Environment configuration

Main variables:

```env
APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=

DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=
CACHE_STORE=
QUEUE_CONNECTION=

REDIS_CLIENT=
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=

BROADCAST_CONNECTION=
FILESYSTEM_DISK=

N8N_WHATSAPP_WEBHOOK_URL=
EVOLUTION_API_URL=
GEMINI_INTERACTIONS_URL=
GEMINI_API_KEY=

VITE_APP_NAME=
VITE_REVERB_APP_KEY=
VITE_REVERB_HOST=
VITE_REVERB_PORT=
VITE_REVERB_SCHEME=
```

The project supports SQLite, MySQL/MariaDB, PostgreSQL, and SQL Server through Laravel configuration. The local environment can use SQLite, while production `.env` should point to the chosen database.

## Running

### Laravel

Runs the development environment configured in the project:

```bash
composer run dev
```

### Frontend

Development server:

```bash
npm run dev
```

Production build:

```bash
npm run build
```

### n8n services

Inside the `n8n/` folder:

```bash
docker compose up -d
```

Services defined in `n8n/docker-compose.yml`:

| Service | Image | Port |
|---|---|---:|
| n8n | `docker.n8n.io/n8nio/n8n:latest` | `5678` |
| Evolution API | `evoapicloud/evolution-api:latest` | `8080` |
| Redis | `redis:7-alpine` | internal |
| PostgreSQL | `postgres:16-alpine` | internal |

Laravel runs separately. Endpoints used by the workflow must be reachable from the environment where n8n is running.

## Main endpoints

| Method | Endpoint | Function |
|---|---|---|
| `POST` | `/api/webhook/evolution` | Receives Evolution API events |
| `POST` | `/api/webhook/order` | Receives completed orders from n8n |
| `POST` | `/api/saveMessage` | Persists messages generated by automation |
| `GET` | `/api/conversation/{remoteJid}/finished` | Checks whether the conversation is finished |
| `GET` | `/api/menu/all` | Lists the full menu |
| `GET` | `/api/menu/available` | Lists available items |
| `GET` | `/api/user` | Returns authenticated user |
| `POST` | `/admin/whatsapp/messages` | Sends message from the panel |
| `GET` | `/admin/whatsapp/conversation-messages` | Retrieves conversation messages |

## Business domains

### WhatsApp messages

`EvolutionService` processes `messages.upsert` events, prevents duplication by `message_id`, saves messages, and forwards data to n8n.

Audio messages are stored in `storage/app/public/whatsapp/audio` and transcribed by the endpoint configured in `GEMINI_INTERACTIONS_URL`.

The panel allows viewing conversations and sending messages through Evolution API.

### Menu

Filament resources and menu endpoints expose items, prices, categories, sizes, and availability.

The n8n agent must query menu tools. Prices and availability should not be inferred only from conversation text.

### Orders

`OrderService` creates orders with initial `pending` status, saves delivery and payment data, fires the `OrderCreated` event, and temporarily marks the conversation as finished in Redis.

The event is broadcast on the public `orders` channel, enabling real-time updates in the panel.

## Admin panel

The Filament panel is available at:

```text
/admin
```

Main resources:

- Menus
- Orders
- WhatsApp and messages

The panel uses authentication, persisted notifications, Livewire, and real-time updates via Reverb/Pusher.

## Testing and quality

Run lint with auto-fix:

```bash
composer run lint
```

Check lint without changing files:

```bash
composer run lint:check
```

Run static analysis:

```bash
composer run types:check
```

Run full suite:

```bash
composer run test
```

The PHPUnit suite uses isolated test settings, including in-memory cache, synchronous queue, array session, and null broadcast.

## Persistence

Main entities:

- `users`: users and authentication
- `orders`: orders, customer, phone, status, total, and timestamps
- `menu`: items, prices, categories, sizes, and availability
- `whatsapp`: messages, sender, type, Evolution API IDs, instance, audio, and conversation

Laravel also uses tables for sessions, cache, and jobs when configured drivers use the database.

## Security and operations

- Do not version `.env`, API keys, tokens, or passwords.
- Change default Docker credentials before exposing services.
- Restrict webhook endpoints by network, signature, or authentication whenever possible.
- Review internal URLs and `pinData` data before exporting the workflow.
- Use HTTPS in internet-facing environments.
- Keep Docker volumes persistent and back up PostgreSQL, Redis, and n8n data.

## License

No license was identified in the repository. Define a license before distributing the project.
