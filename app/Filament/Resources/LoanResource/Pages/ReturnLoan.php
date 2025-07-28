<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Helpers\ConditionTags;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class ReturnLoan extends Page
{
    protected static string $resource = LoanResource::class;

    protected static string $view = 'filament.resources.loan-resource.pages.return-loan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('condition_tags')
                    ->label('Condition Tags')
                    ->multiple()
                    ->searchable()
                    ->options(ConditionTags::grouped())
                    ->placeholder('Select item condition tags')
                    ->required(false)
                    ->afterStateUpdated(function ($state, $set) {
                        if (!$state || empty($state)) {
                            return;
                        }

                        // Check if both good condition and issue tags are selected
                        if (ConditionTags::hasGoodTags($state) && ConditionTags::hasIssueTags($state)) {
                            // Keep only good condition tags
                            $filteredTags = ConditionTags::filterToGoodTags($state);
                            $set('condition_tags', $filteredTags);

                            // Show notification about the conflict
                            Notification::make()
                                ->warning()
                                ->title('Tag Conflict Resolved')
                                ->body('Good condition tags cannot be selected with issue tags. Only good condition tags have been kept.')
                                ->send();
                        }
                    })
                    ->helperText('Note: Good condition tags cannot be selected with issue tags. Selecting a good condition tag will automatically clear any issue tags.'),

                Forms\Components\Textarea::make('return_notes')
                    ->label('Additional Notes')
                    ->placeholder('Add extra notes if necessary')
                    ->rows(3),
            ])
            ->statePath('data');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Return Loan';
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Here you would typically update the loan record
        // For now, we'll just show a success notification
        Notification::make()
            ->success()
            ->title('Loan Return Submitted')
            ->body('The loan return has been processed with condition details.')
            ->send();

        // Redirect back to the loans list
        $this->redirect(route('filament.admin.resources.loans.index'));
    }
}
