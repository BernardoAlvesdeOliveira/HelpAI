<x-filament-panels::page>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @php
        $messages = \App\Models\Whatsapp::query()
            ->whereNotNull('message')
            ->orderBy('created_at')
            ->get();

        $conversations = $messages
            ->groupBy(fn ($message) => $message->remote_jid ?: $message->phone_number ?: 'unknown')
            ->map(function ($conversation, $key) {
                $ordered = $conversation->sortBy('created_at')->values();
                $lastMessage = $ordered->last();
                $firstMessage = $ordered->first();

                return [
                    'key' => $key,
                    'name' => $conversation->pluck('name')->filter()->first()
                        ?: $firstMessage->phone_number
                        ?: 'Contato sem nome',
                    'phone' => $firstMessage->phone_number,
                    'messages' => $ordered->slice(-10)->values(),
                    'has_more' => $ordered->count() > 10,
                    'last_message' => $lastMessage,
                    'unread' => $conversation->where('from_me', false)->count(),
                ];
            })
            ->sortByDesc(fn ($conversation) => $conversation['last_message']->created_at)
            ->values();

        $activeKey = null;
    @endphp

    <script>
        window.whatsappChat = (initialActive) => ({
            active: initialActive,
            search: '',
            draft: '',
            sending: false,
            error: '',
            loadingOlder: false,
            escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, character => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[character]);
            },
            renderMessage(message) {
                const outgoing = Boolean(message.from_me);
                const aiMessage = Boolean(message.ai_message);
                const date = new Date(message.created_at);
                const type = message.message_type && message.message_type !== 'conversation'
                    ? `<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">${this.escapeHtml(message.message_type)}</p>`
                    : '';
                const audio = message.audio_url
                    ? `<audio controls preload="metadata" class="mb-2 max-w-full"><source src="${this.escapeHtml(message.audio_url)}"></audio>`
                    : '';
                const content = message.audio_url
                    ? ''
                    : `<p class="whitespace-pre-wrap break-words">${this.escapeHtml(message.message)}</p>`;
                const bubble = aiMessage
                    ? 'rounded-tr-none bg-violet-100 text-violet-950 dark:bg-violet-950 dark:text-violet-100'
                    : outgoing
                        ? 'rounded-tr-none bg-[#d9fdd3] text-gray-800 dark:bg-emerald-950 dark:text-gray-100'
                        : 'rounded-tl-none bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100';

                return `
                    <div class="flex ${outgoing ? 'justify-end' : 'justify-start'}" data-created-at="${this.escapeHtml(message.created_at)}">
                        <article class="max-w-[85%] rounded-lg px-3 py-2 text-sm shadow-sm sm:max-w-[70%] ${bubble}">
                            ${type}
                            ${audio}
                            ${content}
                            <footer class="mt-1 text-right text-[10px] text-gray-500 dark:text-gray-400">
                                ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                                ${outgoing ? '✓' : ''}
                            </footer>
                        </article>
                    </div>
                `;
            },
            async loadOlder(event) {
                if (event.target.scrollTop > 20 || this.loadingOlder) return;

                const section = event.target.querySelector(`[data-conversation="${CSS.escape(this.active)}"]`);
                const oldest = section?.querySelector('[data-created-at]');
                if (!section || section.dataset.hasMore !== 'true' || !oldest) return;

                this.loadingOlder = true;
                const height = event.target.scrollHeight;

                try {
                    const query = new URLSearchParams({
                        remote_jid: this.active,
                        before: oldest.dataset.createdAt,
                    });
                    const response = await fetch(`{{ route('whatsapp.conversation.messages') }}?${query}`, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) throw new Error('Não foi possível carregar mensagens antigas.');

                    const payload = await response.json();
                    payload.messages.reverse().forEach(message => {
                        section.insertAdjacentHTML('afterbegin', this.renderMessage(message));
                    });
                    section.dataset.hasMore = payload.has_more ? 'true' : 'false';
                    event.target.scrollTop = event.target.scrollHeight - height;
                } catch (exception) {
                    this.error = exception.message;
                } finally {
                    this.loadingOlder = false;
                }
            },
            scrollToBottom() {
                this.$nextTick(() => {
                    if (this.$refs.messages) this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            },
            async sendMessage() {
                if (!this.active || !this.draft.trim() || this.sending) return;

                this.sending = true;
                this.error = '';

                try {
                    const response = await fetch(`{{ route('whatsapp.messages.send') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                        },
                        body: JSON.stringify({ remote_jid: this.active, message: this.draft.trim() }),
                    });

                    if (!response.ok) {
                        throw new Error((await response.json()).message || 'Não foi possível enviar a mensagem.');
                    }

                    this.draft = '';
                    window.location.reload();
                } catch (exception) {
                    this.error = exception.message;
                } finally {
                    this.sending = false;
                }
            },
        });
    </script>

    <div
        x-data="whatsappChat(@js($activeKey))"
        x-init="scrollToBottom()"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950"
    >
        <div class="grid h-[680px] grid-cols-1 lg:grid-cols-[350px_minmax(0,1fr)]">
            <aside class="flex min-h-0 flex-col border-b border-gray-200 dark:border-white/10 lg:border-b-0 lg:border-r">
                <header class="bg-[#075e54] px-5 py-4 text-white">
                    <p class="text-lg font-semibold">Conversas</p>
                    <p class="text-xs text-emerald-100">{{ $conversations->count() }} contatos</p>
                </header>
                <div class="border-b border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-900">
                    <input x-model="search" type="search" placeholder="Pesquisar conversa" class="w-full rounded-lg border-0 bg-white py-2 px-3 text-sm ring-1 ring-gray-200 focus:ring-2 focus:ring-emerald-500 dark:bg-gray-800 dark:ring-white/10">
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    @forelse ($conversations as $conversation)
                        @php
                            $name = $conversation['name'];
                            $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
                        <button
                            type="button"
                            x-show="{{ json_encode(mb_strtolower($name)) }}.includes(search.toLowerCase())"
                            x-on:click="active = @js($conversation['key']); scrollToBottom()"
                            x-bind:class="active === @js($conversation['key']) ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'hover:bg-gray-50 dark:hover:bg-gray-900'"
                            class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left dark:border-white/5"
                        >
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white">{{ $initials ?: '?' }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="flex justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $name }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $conversation['last_message']->created_at?->format('H:i') }}</span>
                                </span>
                                <span class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $conversation['last_message']->message }}</span>
                            </span>
                        </button>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">Nenhuma mensagem encontrada.</div>
                    @endforelse
                </div>
            </aside>

            <main class="flex min-h-0 flex-col bg-[#efeae2] dark:bg-gray-900">
                @if ($conversations->isNotEmpty())
                    <template x-if="!active">
                        <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                            <x-heroicon-o-chat-bubble-left-right class="mb-4 h-14 w-14 text-emerald-600/70" />
                            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Nenhum contato selecionado</h2>
                            <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">Selecione uma conversa ao lado para visualizar as mensagens.</p>
                        </div>
                    </template>
                    <div x-show="active" class="flex min-h-0 flex-1 flex-col">
                        <header class="border-b border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-gray-950">
                            @foreach ($conversations as $conversation)
                                <template x-if="active === @js($conversation['key'])">
                                    <div>
                                        <h2 class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $conversation['name'] }}</h2>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $conversation['phone'] ?: 'conversa do WhatsApp' }}</p>
                                    </div>
                                </template>
                            @endforeach
                        </header>
                        <div x-ref="messages" x-on:scroll.passive="loadOlder($event)" class="min-h-0 flex-1 overflow-y-auto px-4 py-6 sm:px-8">
                            @foreach ($conversations as $conversation)
                                <section x-show="active === @js($conversation['key'])" data-conversation="{{ $conversation['key'] }}" data-has-more="{{ $conversation['has_more'] ? 'true' : 'false' }}" class="mx-auto max-w-3xl space-y-2">
                                    @php $lastDate = null; @endphp
                                    @foreach ($conversation['messages'] as $message)
                                        @php
                                            $date = $message->created_at?->copy()->timezone(config('app.timezone'));
                                            $messageDate = $date?->format('Y-m-d');
                                        @endphp
                                        @if ($messageDate !== $lastDate)
                                            <div class="my-4 flex justify-center">
                                                <span class="rounded-md bg-white/80 px-3 py-1 text-[11px] text-gray-500 shadow-sm">
                                                    {{ $date?->isToday() ? 'Hoje' : $date?->format('d/m/Y') }}
                                                </span>
                                            </div>
                                            @php $lastDate = $messageDate; @endphp
                                        @endif
                                        <div data-created-at="{{ $message->created_at?->toIso8601String() }}" class="flex {{ $message->from_me ? 'justify-end' : 'justify-start' }}">
                                            <article class="max-w-[85%] rounded-lg px-3 py-2 text-sm shadow-sm sm:max-w-[70%] {{ $message->ai_message ? 'rounded-tr-none bg-violet-100 text-violet-950 dark:bg-violet-950 dark:text-violet-100' : ($message->from_me ? 'rounded-tr-none bg-[#d9fdd3] text-gray-800 dark:bg-emerald-950 dark:text-gray-100' : 'rounded-tl-none bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100') }}">
                                                @if ($message->audio_url)
                                                    <audio controls preload="metadata" class="mb-2 max-w-full">
                                                        <source src="{{ $message->audio_url }}">
                                                    </audio>
                                                @else
                                                    <p class="whitespace-pre-wrap break-words">{{ $message->message }}</p>
                                                @endif
                                                <footer class="mt-1 text-right text-[10px] text-gray-500 dark:text-gray-400">{{ $date?->format('H:i') }} @if ($message->from_me) ✓ @endif</footer>
                                            </article>
                                        </div>
                                    @endforeach
                                </section>
                            @endforeach
                        </div>
                        <form x-on:submit.prevent="sendMessage()" class="border-t border-gray-200 bg-[#f0f2f5] p-3 dark:border-white/10 dark:bg-gray-950">
                            <p x-show="error" x-text="error" class="mb-2 text-xs text-red-600"></p>
                            <div class="flex items-end gap-2">
                                <textarea x-model="draft" x-on:keydown.enter.prevent="if (!$event.shiftKey) sendMessage()" rows="1" maxlength="4096" placeholder="Digite uma mensagem" class="max-h-32 min-h-11 flex-1 resize-none rounded-xl border-0 bg-white px-4 py-3 text-sm text-gray-800 shadow-sm ring-1 ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-emerald-500 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:ring-white/10"></textarea>
                                <button type="submit" x-bind:disabled="sending || !draft.trim()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#128c7e] text-white disabled:opacity-50" aria-label="Enviar mensagem">
                                    <x-heroicon-o-paper-airplane class="h-5 w-5" />
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="flex flex-1 items-center justify-center text-sm text-gray-500">Nenhuma conversa disponível</div>
                @endif
            </main>
        </div>
    </div>
</x-filament-panels::page>
