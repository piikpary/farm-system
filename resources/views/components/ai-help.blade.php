<div x-data="aiHelp()" class="ai-help-box">
    <button type="button" class="ai-help-button" @click="open = true">
        🤖 AI Help
    </button>

    <div class="ai-help-panel" x-show="open" x-transition>
        <div class="ai-help-header">
            <div>
                <strong>{{ __('pages.ai_help') }}</strong>
                <small>{{ __('pages.ai_help_subtitle') }}</small>
            </div>

            <button type="button" @click="open = false">×</button>
        </div>

        <div class="ai-help-body" id="aiHelpBody">
            <template x-for="message in messages" :key="message.id">
                <div class="ai-message" :class="message.type">
                    <span x-text="message.text"></span>
                </div>
            </template>

            <div x-show="loading" class="ai-message bot">
                {{ __('pages.ai_thinking') }}
            </div>
        </div>

        <div class="ai-help-samples">
            <button type="button" @click="askSample('{{ __('pages.ai_sample_tractor') }}')">
                {{ __('pages.ai_sample_tractor') }}
            </button>

            <button type="button" @click="askSample('{{ __('pages.ai_sample_stock_fuel') }}')">
                {{ __('pages.ai_sample_stock_fuel') }}
            </button>

            <button type="button" @click="askSample('{{ __('pages.ai_sample_work_log') }}')">
                {{ __('pages.ai_sample_work_log') }}
            </button>
        </div>

        <div class="ai-help-input">
            <input type="text"
                   x-model="question"
                   @keydown.enter="ask"
                   placeholder="{{ __('pages.ask_ai_placeholder') }}">

            <button type="button" @click="ask" :disabled="loading">
                {{ __('pages.send') }}
            </button>
        </div>
    </div>
</div>

<script>
    function aiHelp() {
        return {
            open: false,
            question: '',
            loading: false,
            messages: [
                {
                    id: Date.now(),
                    type: 'bot',
                    text: @js(__('pages.ai_welcome_message'))
                }
            ],

            askSample(text) {
                this.question = text;
                this.ask();
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const body = document.getElementById('aiHelpBody');
                    if (body) {
                        body.scrollTop = body.scrollHeight;
                    }
                });
            },

            async ask() {
                if (!this.question.trim() || this.loading) {
                    return;
                }

                const userQuestion = this.question.trim();

                this.messages.push({
                    id: Date.now() + Math.random(),
                    type: 'user',
                    text: userQuestion
                });

                this.question = '';
                this.loading = true;
                this.scrollToBottom();

                try {
                    const response = await fetch('{{ route('ai-help.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            message: userQuestion,
                            module: document.body.dataset.module || window.location.pathname || 'general',
                        }),
                    });

                    const data = await response.json();

                    const aiText = data.answer || data.reply || data.message || data.content;

                    if (!response.ok || !data.success || !aiText) {
                        console.log('AI Help error response:', data);

                        this.messages.push({
                            id: Date.now() + Math.random(),
                            type: 'bot',
                            text: data.message || @js(__('pages.ai_no_answer'))
                        });

                        return;
                    }

                    this.messages.push({
                        id: Date.now() + Math.random(),
                        type: 'bot',
                        text: aiText
                    });

                } catch (e) {
                    console.log('AI Help fetch error:', e);

                    this.messages.push({
                        id: Date.now() + Math.random(),
                        type: 'bot',
                        text: @js(__('pages.ai_not_available'))
                    });
                } finally {
                    this.loading = false;
                    this.scrollToBottom();
                }
            }
        }
    }
</script>