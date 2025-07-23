<?php

namespace Ngankt2\FilamentChatAgent\Components;
use Illuminate\Support\Facades\Cache;
use Ngankt2\FilamentChatAgent\Actions\AiFunctionAction;
use Ngankt2\FilamentChatAgent\FilamentChatAgentPlugin;
use Ngankt2\FilamentChatAgent\Models\ChatQuestionTemplate;
use OpenAI;
use Livewire\Attributes\Session;
use Livewire\Component;

class ChatAgentComponent extends Component
{

    public string $name;

    public string $buttonText;

    public string $buttonIcon;

    public string $sendingText;

    public array $messages;

    #[Session]
    public string $question;

    public string $questionContext;

    public string $pageWatcherEnabled;

    public string $pageWatcherSelector;

    public string $winWidth;

    public string $winPosition;

    public bool $showPositionBtn;

    public bool $panelHidden;

    public string|bool $logoUrl;

    private string $sessionKey;

    public function __construct()
    {
        $this->sessionKey = auth()->id() . '-chatgpt-agent-messages-xx';
    }

    public function mount(): void
    {
        $this->panelHidden = session($this->sessionKey . '-panelHidden', true);
        $this->winWidth = "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";";
        $this->winPosition = session($this->sessionKey . '-winPosition', '');
        $this->showPositionBtn = true;
        $this->messages = session(
            $this->sessionKey,
            $this->getDefaultMessages()
        );
        $this->question = "";
        $this->name = filament('filament-ai-chat-agent')->getBotName();
        $this->buttonText = filament('filament-ai-chat-agent')->getButtonText();
        $this->buttonIcon = filament('filament-ai-chat-agent')->getButtonIcon();
        $this->sendingText = filament('filament-ai-chat-agent')->getSendingText();
        $this->questionContext = '';
        $this->pageWatcherEnabled = filament('filament-ai-chat-agent')->isPageWatcherEnabled();
        $this->pageWatcherSelector = filament('filament-ai-chat-agent')->getPageWatcherSelector();
        $this->logoUrl = filament('filament-ai-chat-agent')->getLogoUrl();
    }

    public function render()
    {
        return view('filament-ai-chat-agent::livewire.chat-bot');
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->question))) {
            $this->question = "";
            return;
        }
        $this->messages[] = [
            "role" => 'user',
            "content" => $this->question,
        ];

        $this->chat();
        $this->question = "";
        $this->dispatch('sendmessage', ['message' => $this->question]);
    }

    public function changeWinWidth(): void
    {
        if ($this->winWidth == "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";") {
            $this->winWidth = "width:100%;";
            $this->showPositionBtn = false;
        } else {
            $this->winWidth = "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";";
            $this->showPositionBtn = true;
        }
    }

    public function changeWinPosition(): void
    {
        if ($this->winPosition != "left") {
            $this->winPosition = "left";
        } else {
            $this->winPosition = "";
        }
        session([$this->sessionKey . '-winPosition' => $this->winPosition]);
    }

    public function resetSession(): void
    {
        request()->session()->forget($this->sessionKey);
        $this->messages = $this->getDefaultMessages();
    }

    public function togglePanel(): void
    {
        $this->panelHidden = !$this->panelHidden;
        session([$this->sessionKey . '-panelHidden' => $this->panelHidden]);
    }

    protected function _chat(): void
    {
        $provider = config('filament-ai-chat-agent.default','openai');
        $config = config("filament-ai-chat-agent.providers.{$provider}");

        $client = \OpenAI::factory()
            ->withApiKey($config['api_key'])
            ->withBaseUri($config['base_url']) // default đã là https://api.openai.com/v1
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => $config['timeout'] ?? 45,
            ]))
            ->make();

        $payload = array_merge([
            'model' => $config['model'],
            'messages' => $this->messages,
        ], $config['options'] ?? []);

        $client->embeddings();

        $response = $client->chat()->create($payload);

        $this->messages[] = $response->choices[0]?->message->toArray() ?? [
            'role' => 'assistant',
            'content' => 'Xin lỗi, không có phản hồi từ AI.',
        ];

        request()->session()->put($this->sessionKey, $this->messages);
    }

    protected function chat(): void
    {
        $provider = config('filament-ai-chat-agent.default', 'openai');
        $config = config("filament-ai-chat-agent.providers.{$provider}");

        $client = OpenAI::factory()
            ->withApiKey($config['api_key'])
            ->withBaseUri($config['base_url'])
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => $config['timeout'] ?? 45,
            ]))
            ->make();


        // Phân tích ý định trước
        $intentData = $this->extractIntent($client, $this->question);
        $functionName = $intentData['intent'] ?? null;
        $parameters = $intentData['parameters'] ?? [];

        if ($functionName) {
            $result = $this->callAction($functionName, $parameters);
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $result['message'] ?? 'Không thể xử lý yêu cầu.',
            ];
        } else {
            // Fallback: Tạo embedding để tìm mẫu gần nhất
            $cacheKey = 'question_embedding_' . md5($this->question);
            $questionEmbedding = Cache::remember($cacheKey, now()->addHours(1), function () use ($client) {
                return $client->embeddings()->create([
                    'model' => 'text-embedding-ada-002',
                    'input' => $this->question,
                ])->embeddings[0]->embedding;
            });

            $closestTemplate = $this->findClosestQuestionTemplate($questionEmbedding);
            if ($closestTemplate && $closestTemplate->function_name) {
                $result = $this->callAction($closestTemplate->function_name, $this->question);
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $result['message'] ?? 'Không thể xử lý yêu cầu.',
                ];
            } else {
                $payload = array_merge([
                    'model' => $config['model'],
                    'messages' => $this->messages,
                    'functions' => filament('filament-ai-chat-agent')->getFunctions(),
                ], $config['options'] ?? []);

                $response = $client->chat()->create($payload);
                if ($response->choices[0]->message->function_call) {
                    $functionName = $response->choices[0]->message->function_call->name;
                    $arguments = json_decode($response->choices[0]->message->function_call->arguments, true) ?? [];
                    $result = $this->callAction($functionName, $arguments);
                    $this->messages[] = [
                        'role' => 'assistant',
                        'content' => $result['message'] ?? 'Không thể xử lý yêu cầu.',
                    ];
                } else {
                    $this->messages[] = $response->choices[0]?->message->toArray() ?? [
                        'role' => 'assistant',
                        'content' => 'Xin lỗi, không có phản hồi từ AI.',
                    ];
                }
            }
        }

        request()->session()->put($this->sessionKey, $this->messages);
    }


    protected function callAction(string $functionName, $arguments): array
    {
        foreach ($this->actions as $actionClass) {
            $action = new $actionClass();
            if ($action->getFunctionName() === $functionName) {
                $parameters = $this->extractParameters($action, $arguments, $functionName);
                return $action->execute($parameters);
            }
        }
        return ['message' => 'Chức năng không được hỗ trợ.'];
    }

    protected function extractParameters(AiFunctionAction $action, $arguments, string $functionName): array
    {
        $parametersSchema = $action->getParametersSchema();
        $parameters = is_array($arguments) ? $arguments : [];
        return $parameters;
    }

    protected function extractIntent($client, string $question): array
    {
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Phân tích câu hỏi và trả về ý định (intent) và tham số (parameters). Ví dụ: {"intent": "GetYesterdayPostsAction", "parameters": {}} hoặc {"intent": "GetUserPostsAction", "parameters": {"username": "John"}}. Các ý định có thể là: ' . collect(filament('filament-ai-chat-agent')->getFunctions())->pluck('name')->implode(', ') . '.'],
                ['role' => 'user', 'content' => $question],
            ],
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    protected function isValidAction(string $functionName): bool
    {
        return collect($this->actions)->contains(fn($actionClass) => (new $actionClass())->getFunctionName() === $functionName);
    }

    protected function findClosestQuestionTemplate(array $questionEmbedding): ?ChatQuestionTemplate
    {
        return Cache::remember('question_templates', 3600, function () {
            return ChatQuestionTemplate::all();
        })->reduce(function ($closest, $template) use ($questionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $template->embedding);
            if ($similarity > ($closest['similarity'] ?? -1) && $similarity > 0.8) {
                return ['template' => $template, 'similarity' => $similarity];
            }
            return $closest;
        }, ['similarity' => -1])['template'] ?? null;
    }

    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        $normA = sqrt($normA);
        $normB = sqrt($normB);
        return $normA * $normB ? $dotProduct / ($normA * $normB) : 0;
    }

    protected function getDefaultMessages(): array
    {
        return filament('filament-ai-chat-agent')->getStartMessage() ?
            [
                ['role' => 'assistant', 'content' => filament('filament-ai-chat-agent')->getStartMessage()],
            ] : [];
    }
}
