<?php

namespace Leandrocfe\FilamentPtbrFormFields\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentPtbrFormFields\CepFieldMode;
use Leandrocfe\FilamentPtbrFormFields\Providers\CepProviderInterface;
use Livewire\Component;

trait HasCepModes
{
    protected CepFieldMode $mode = CepFieldMode::ON_BLUR;

    /**
     * Set the CEP lookup mode.
     */
    public function mode(CepFieldMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get the current lookup mode.
     */
    public function getMode(): CepFieldMode
    {
        return $this->mode;
    }

    /**
     * Configure the field according to the selected mode.
     */
    protected function configureFieldMode(CepProviderInterface $provider, callable $callback): static
    {
        return match ($this->getMode()) {
            CepFieldMode::ON_BLUR => $this->configureOnBlurMode($provider, $callback),
            CepFieldMode::SUFFIX => $this->configureSuffixMode($provider, $callback),
            default => $this,
        };
    }

    /**
     * Configure ON_BLUR mode
     */
    protected function configureOnBlurMode(CepProviderInterface $provider, callable $callback): static
    {
        return $this
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Set $set, TextInput $component, Component $livewire) use ($provider, $callback) {

                $livewire->validateOnly($component->getStatePath());

                $response = $this->fetchCepData($state, $provider);

                if (blank($response)) {
                    $livewire->addError($component->getStatePath(), $this->getDefaultErrorMessage());
                }

                $callback($set, $response);
            });
    }

    /**
     * Configure SUFFIX mode
     */
    protected function configureSuffixMode(CepProviderInterface $provider, callable $callback): static
    {
        return $this
            ->suffixAction(function () use ($provider, $callback) {
                return Action::make('searchCep')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->action(function (?string $state, Set $set, TextInput $component, Component $livewire) use ($provider, $callback) {

                        $livewire->validateOnly($component->getStatePath());

                        $response = $this->fetchCepData($state, $provider);

                        if (blank($response)) {
                            $livewire->addError($component->getStatePath(), $this->getDefaultErrorMessage());
                        }

                        $callback($set, $response);
                    })
                    ->cancelParentActions();
            });
    }

    /**
     * Fetch CEP data through the provider.
     */
    protected function fetchCepData(?string $cep, CepProviderInterface $provider): null|array|\Illuminate\Support\Collection
    {
        if (blank($cep)) {
            return null;
        }

        return $provider->fetch($cep);
    }

    protected function validateCep(string $cep, Component $livewire, TextInput $component): bool
    {
        dd($cep, $livewire, $component);
    }
}
