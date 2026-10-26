<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

abstract class ContentRequest extends FormRequest
{
    protected string $modelClass;

    protected string $routeParameter;

    public function authorize(): bool
    {
        $post = $this->route($this->routeParameter);

        return $post
            ? (bool) $this->user()?->can('update', $post)
            : (bool) $this->user()?->can('create', $this->modelClass);
    }
}
