<?php

namespace App\Http\Requests;

use App\Rules\ValidUserForTaskRule;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $timestamp = Carbon::today()->timestamp;
        $verifierTypes = collect($this->verifier_types);

        return [
            'name' => 'required|string|min:3|max:43',
            'text' => 'required|string|min:10|max:3000',
            'priority' => [
                'required',
                Rule::in(config('tasks.priorities')),
            ],
            'rewards' => 'required|array|min:1',
            'rewards.*.type' => [
                Rule::in(config('reward.types')),
                'distinct:ignore_case',
            ],
            'rewards.*.value' => 'required|string|min:1',
            'conditions' => 'array',
            'conditions.*.type' => [
                Rule::in(config('conditions.types')),
                'distinct:ignore_case',
            ],
            'conditions.*.value' => 'required|string|min:1',
            'conditions.*.operator' => [
                Rule::in(config('conditions.operators')),
                'distinct:ignore_case',
            ],
            'new_images' => 'array',
            'new_images.*' => 'mimes:jpg,jpeg,png,pdf|max:10000',
            'delete_image_ids' => 'array',
            'delete_image_ids.*' => 'integer|exists:media,id',
            'verifier_driver' => [
                'nullable',
                Rule::in(config('verifier.drivers')),
            ],
            'verifier_types' => 'required_with:verifier_driver|array',
            'verifier_types.*' => [
                Rule::in(config('verifier.types')[$this->verifier_driver] ?? []),
                'distinct:ignore_case',
            ],
            'tweet_words' => [
//                Rule::requiredIf(fn () => $verifierTypes->contains('twitter_tweet')),
                'array',
            ],
            'tweet_words.*' => [
                'string',
                'distinct:ignore_case',
            ],
            'twitter_space' => [
                Rule::requiredIf(fn () => $verifierTypes->contains('twitter_space')),
                'string',
                'regex:/^https?:\/\/(www.)?twitter\.com\/i\/spaces?\/(?<space>[a-zA-Z0-9]{1,13})/i',
                'twitter_space',
            ],
            'twitter_tweet' => [
                Rule::requiredIf(function () {
                    return in_array($this->verifier_types, [
                        'twitter_like',
                        'twitter_reply',
                        'twitter_retweet',
                    ]);
                }),
                'regex:/^(https?:\/\/)?((www.|m.|mobile.)?twitter\.com)\/(?:#!\/)?(\w+)\/status?\/(?<tweet>\d+)/i',
                'twitter_tweet',
            ],
            'twitter_follow' => [
                Rule::requiredIf(fn () => $verifierTypes->contains('twitter_follow')),
                'string',
                'regex:/(^|[^@\w])@(\w{1,15})\b/i',
                'twitter_handle',
            ],
            'discord_invite' => [
                Rule::requiredIf(fn () => $verifierTypes->contains('discord_invite')),
                'string',
                'regex:/^(https?:\/\/)?(discord(?:(?:app)?\.com\/invite|\.gg)(?:\/invite)?)\/(?<code>[\w-]{2,255})/i',
                'discord_invite',
            ],
            'telegram_invite' => [
                Rule::requiredIf(fn () => $verifierTypes->contains('telegram_invite')),
                'string',
                'telegram_invite',
            ],
            'default_tweet' => [
//                Rule::requiredIf(fn () => $verifierTypes->contains('twitter_tweet')),
                'nullable',
                'string',
                'min:1',
            ],
            'default_reply' => [
//                Rule::requiredIf(fn () => $verifierTypes->contains('twitter_reply')),
                'nullable',
                'string',
                'min:1',
            ],
            'project_id' => 'required|integer|exists:projects,id',
            'activity_id' => 'nullable|integer|exists:activities,id',
            'coin_type_id' => 'nullable|integer|exists:coin_types,id',
            'assign_user_ids' => 'array',
            'assign_user_ids.*' => [
                'integer',
                new ValidUserForTaskRule($this->min_level, $this->max_level, $this->activity_id),
            ],
            'min_level' => 'integer|min:0|nullable',
            'max_level' => 'integer|min:0|nullable|gte:min_level',
            'started_at' => [
                'required',
                'integer',
                'gte:'.$timestamp,
            ],
            'ended_at' => 'required|integer|gt:started_at',
            'number_of_winners' => [
                'integer',
                'min:1',
            ],
            'number_of_invites' => [
                'integer',
                'min:1',
                'max:1000000000',
            ],
            'level_coefficient' => 'boolean',
            'number_of_participants' => 'integer|min:1',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->number_of_invites > 0) {
            $this->offsetUnset('number_of_winners');
        }

        if ($this->number_of_winners > 0) {
            $this->offsetUnset('number_of_invites');
        }

        if (!empty($this->assign_user_ids)) {
            $this->offsetUnset('number_of_participants');
        }

        $this->merge([
            'max_level' => !$this->has('max_level')
                ? $this->get('min_level')
                : $this->get('max_level'),
            'level_coefficient' => filter_var($this->level_coefficient, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
