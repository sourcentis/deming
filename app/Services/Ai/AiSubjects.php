<?php

namespace App\Services\Ai;

use App\Models\Action;
use App\Models\Control;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use App\Services\Ai\Context\ActionContext;
use App\Services\Ai\Context\ContextBuilder;
use App\Services\Ai\Context\ControlContext;
use App\Services\Ai\Context\MeasureContext;
use App\Services\Ai\Context\RiskContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry of the object types the AI agent can analyse.
 *
 * Beware of the inverted naming: the Control model is a "security measure"
 * in the UI, and the Measure model is a "control".
 * Adding a type only needs an entry here, a prompt and an @include in its view.
 */
final class AiSubjects
{
    public const TYPES = [
        // Security measure ("mesure" in the UI)
        'control' => [
            'model' => Control::class,
            'tag' => 'mesure',
            'prompt' => 'ai.prompt_control',
            'context' => ControlContext::class,
            'label' => 'cruds.control.show',
        ],
        // Control ("contrôle" in the UI)
        'measure' => [
            'model' => Measure::class,
            'tag' => 'controle',
            'prompt' => 'ai.prompt_measure',
            'context' => MeasureContext::class,
            'label' => 'cruds.measure.title_singular',
        ],
        'action' => [
            'model' => Action::class,
            'tag' => 'plan_action',
            'prompt' => 'ai.prompt_action',
            'context' => ActionContext::class,
            'label' => 'cruds.action.show',
        ],
        'risk' => [
            'model' => Risk::class,
            'tag' => 'risque',
            'prompt' => 'ai.prompt_risk',
            'context' => RiskContext::class,
            'label' => 'cruds.risk.singular',
        ],
    ];

    /** @return array<int, string> */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    /** @return array{model: class-string<Control|Measure|Action|Risk>, tag: string, prompt: string, context: class-string<ContextBuilder>, label: string} */
    public static function get(string $type): array
    {
        if (! array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException('Unknown AI subject type');
        }

        return self::TYPES[$type];
    }

    public static function find(string $type, int $id): ?Model
    {
        return self::get($type)['model']::query()->find($id);
    }

    /**
     * Single access rule to the AI agent, used by the endpoints and the views:
     * the object must be viewable by the user, who is neither auditor nor API.
     */
    public static function canUseAi(?User $user, string $type, Model $object): bool
    {
        if ($user === null || $user->isAuditor() || $user->isAPI()) {
            return false;
        }

        return self::get($type)['model']::isViewableBy($user, (int) $object->getKey());
    }

    /**
     * Context exactly as sent to the agent: minimized, then personal data masked.
     *
     * @return array<string, mixed>
     */
    public static function context(string $type, Model $object): array
    {
        $builder = new (self::get($type)['context'])();

        return (new PersonalDataRedactor())->redactArray($builder->build($object));
    }

    // Name shown in the panel title
    public static function title(string $type, Model $object): string
    {
        $name = (string) $object->getAttribute('name');

        return match ($type) {
            'control' => trim($object->getAttribute('clause') . ' - ' . $name, ' -'),
            'action' => trim($object->getAttribute('reference') . ' - ' . $name, ' -'),
            default => $name,
        };
    }
}
