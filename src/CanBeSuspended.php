<?php

namespace Digikraaft\ModelSuspension;

use Carbon\Carbon;
use Digikraaft\ModelSuspension\Events\ModelSuspensionChanged;
use Digikraaft\ModelSuspension\Exceptions\InvalidDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait CanBeSuspended
{
    public function suspensions(): MorphMany
    {
        return $this->morphMany($this->getSuspensionModelClassName(), 'model', 'model_type', $this->getModelKeyColumnName())
            ->latest('id');
    }

    public function suspension()
    {
        return $this->latestSuspension();
    }

    private function latestSuspension()
    {
        return $this->suspensions()->first();
    }

    public function suspend(?int $days = 0, ?string $reason = null): self
    {
        $suspended_until = null;

        if ($days > 0) {
            $suspended_until = now()->addDays($days);
        }

        return $this->createSuspension($suspended_until, $reason);
    }

    public function isSuspended(): bool
    {
        if (! $this->suspension()) {
            return false;
        }

        if ($this->suspension()->is_suspended === null && $this->suspension()->suspended_until === null) {
            return false;
        }

        if (Carbon::parse($this->suspension()->suspended_until)->lessThan(now()->toDateString())) {
            return false;
        }

        if ($this->suspension()->is_suspended != true) {
            return false;
        }

        return true;
    }

    public function unsuspend(): self
    {
        return $this->deleteSuspension();
    }

    /**
     *
     * @return bool
     */
    public function hasEverBeenSuspended(): bool
    {
        $suspensions = $this->relationLoaded('suspensions') ? $this->suspensions : $this->suspensions();

        return $suspensions->count() > 0;
    }

    public function createSuspension($suspended_until = null, ?string $reason = null)
    {
        $this->suspensions()->create([
            'is_suspended' => true,
            'suspended_until' => $suspended_until,
            'reason' => $reason,
        ]);

        event(new ModelSuspensionChanged($this, $this->suspension()));

        return $this;
    }

    private function deleteSuspension(): self
    {
        $this->suspension()->update([
            'is_suspended' => null,
            'suspended_until' => null,
            'deleted_at' => now(),
        ]);

        event(new ModelSuspensionChanged($this, $this->suspension()));

        return $this;
    }

    public function numberOfTimesSuspended(?Carbon $from = null, ?Carbon $to = null): int
    {
        if (! $this->suspensions()->exists()) {
            return 0;
        }

        if ($from && $to) {
            if ($from->greaterThan($to)) {
                throw InvalidDate::from();
            }

            return $this->suspensions()
                    ->whereBetween(
                        'created_at',
                        [$from->toDateTimeString(), $to->toDateTimeString()]
                    )->count();
        }

        return $this->suspensions()->count();
    }

    protected function getSuspensionTableName(): string
    {
        $modelClass = $this->getSuspensionModelClassName();

        return (new $modelClass)->getTable();
    }

    protected function getModelKeyColumnName(): string
    {
        return config('model-suspension.model_primary_key_attribute') ?? 'model_id';
    }

    protected function getSuspensionModelClassName(): string
    {
        return config('model-suspension.suspension_model');
    }

    protected function getSuspensionModelType(): string
    {
        return array_search(static::class, Relation::morphMap()) ?: static::class;
    }

    public function scopeAllSuspensions(Builder $builder)
    {
        $builder
            ->whereHas(
                'suspensions',
                function (Builder $query) {
                    $query
                        ->whereIn(
                            'id',
                            function (QueryBuilder $query) {
                                $query
                                    ->select(DB::raw('max(id)'))
                                    ->from($this->getSuspensionTableName())
                                    ->where('model_type', $this->getSuspensionModelType())
                                    ->whereColumn($this->getModelKeyColumnName(), $this->getQualifiedKeyName());
                            }
                        );
                }
            );
    }

    public function scopeActiveSuspensions(Builder $builder)
    {
        $builder
            ->whereHas(
                'suspensions',
                function (Builder $query) {
                    $query
                        ->whereIn(
                            'id',
                            function (QueryBuilder $query) {
                                $query
                                    ->select(DB::raw('max(id)'))
                                    ->from($this->getSuspensionTableName())
                                    ->where('model_type', $this->getSuspensionModelType())
                                    ->whereColumn($this->getModelKeyColumnName(), $this->getQualifiedKeyName());
                            }
                        )->whereNotNull('is_suspended')
                        ->orWhereDate('suspended_until', '>=', now()->toDateString());
                }
            );
    }
    public function scopeNonActiveSuspensions(Builder $builder)
    {
        $builder
            ->whereDoesntHave('suspensions')
            ->orWhereHas(
                'suspensions',
                function (Builder $query) {
                    $query
                        ->whereIn(
                            'id',
                            function (QueryBuilder $query) {
                                $query
                                    ->select(DB::raw('max(id)'))
                                    ->from($this->getSuspensionTableName())
                                    ->where('model_type', $this->getSuspensionModelType())
                                    ->whereColumn($this->getModelKeyColumnName(), $this->getQualifiedKeyName());
                            }
                        )->where(function ($query) {
                            $query->whereNull('is_suspended');
                        })
                        ->orWhere(function ($query) {
                            $query->orWhereDate('suspended_until', '<=', now()->toDateString());
                        });
                }
            );
    }
}
