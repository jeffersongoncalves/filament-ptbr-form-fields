<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Leandrocfe\FilamentPtbrFormFields\Providers\CepProviderInterface;
use Leandrocfe\FilamentPtbrFormFields\Providers\ViaCepProvider;
use Livewire\Component as Livewire;

class Cep extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();
        $this
            ->minLength(9)
            ->mask('99999-999')
            ->placeholder('00000-000');
    }

    protected CepFieldMode $mode = CepFieldMode::ON_BLUR;

    public function mode(CepFieldMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): CepFieldMode
    {
        return $this->mode;
    }

    protected function providerSendRequest(string $state, CepProviderInterface $providerInstance): null|Collection|array
    {
        return $providerInstance->fetch($state);
    }

    protected function getConfiguredField(CepProviderInterface $providerInstance, callable $callback): static
    {
        return match ($this->getMode()) {
            default => $this,
            CepFieldMode::ON_BLUR => $this
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Set $set, Livewire $livewire) use ($providerInstance, $callback) {
                    $response = $this->providerSendRequest($state, $providerInstance);
                    $callback($set, $response);
                }),
        };
    }

    public function api(string|CepProviderInterface $provider, callable $callback): static
    {
        $providerInstance = is_string($provider) ? new $provider : $provider;

        if (! $providerInstance instanceof CepProviderInterface) {
            throw new \InvalidArgumentException('The provider must implement the CepProviderInterface interface.');
        }

        $this->getConfiguredField($providerInstance, $callback);

        return $this;

        //        $this
        //            ->minLength(9)
        //            ->mask('99999-999')
        //            ->afterStateUpdated(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $cepRequest) {
        //                $cepRequest($state, $livewire, $set, $component, $errorMessage);
        //            })
        //            ->suffixAction(function () use ($mode, $errorMessage, $cepRequest) {
        //                if ($mode === 'suffix') {
        //                    return Action::make('search-action')
        //                        ->label('Buscar CEP')
        //                        ->icon('heroicon-o-magnifying-glass')
        //                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $cepRequest) {
        //                            $cepRequest($state, $livewire, $set, $component, $errorMessage);
        //                        })
        //                        ->cancelParentActions();
        //                }
        //            })
        //            ->prefixAction(function () use ($mode, $errorMessage, $cepRequest) {
        //                if ($mode === 'prefix') {
        //                    return Action::make('search-action')
        //                        ->label('Buscar CEP')
        //                        ->icon('heroicon-o-magnifying-glass')
        //                        ->action(function ($state, Livewire $livewire, Set $set, Component $component) use ($errorMessage, $cepRequest) {
        //                            $cepRequest($state, $livewire, $set, $component, $errorMessage);
        //                        })
        //                        ->cancelParentActions();
        //                }
        //            });
        //
        //        return $this;
    }

    /**
     * @deprecated Use api() method instead. Will be removed in v5.0
     */
    public function viaCep(string $mode = 'suffix', string $errorMessage = 'CEP inválido.', array $setFields = []): static
    {
        return $this->api(
            provider: ViaCepProvider::class,
            callback: function ($set, $response) use ($setFields) {
                foreach ($setFields as $key => $value) {
                    $set($key, $response[$value] ?? null);
                }
            },
        );
    }
}
