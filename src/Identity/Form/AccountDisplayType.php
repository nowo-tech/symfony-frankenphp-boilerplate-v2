<?php

declare(strict_types=1);

namespace App\Identity\Form;

use App\Identity\Entity\User;
use App\Shared\Form\FormKitAbstractType;
use InvalidArgumentException;
use Override;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccountDisplayType extends FormKitAbstractType
{
    public const string SECTION_APPEARANCE = 'appearance';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (self::SECTION_APPEARANCE !== $options['section']) {
            throw new InvalidArgumentException('Unknown display section.');
        }

        $choices = [];
        foreach ($options['enabled_locales'] as $locale) {
            $choices[strtoupper($locale)] = $locale;
        }
        $this->withBuilder($builder, function () use ($choices): void {
            $this->addChoiceField('preferredLocale', ['choices' => $choices, 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredTheme', ['choices' => ['preferences.theme_light' => 'light', 'preferences.theme_dark' => 'dark'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredContentWidth', ['choices' => ['preferences.width_content' => 'content', 'preferences.width_full' => 'full'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredUiDensity', ['choices' => ['preferences.density_comfortable' => 'comfortable', 'preferences.density_compact' => 'compact'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredFontScale', ['choices' => ['preferences.font_scale_sm' => 'sm', 'preferences.font_scale_md' => 'md', 'preferences.font_scale_lg' => 'lg'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredContrast', ['choices' => ['preferences.contrast_system' => 'system', 'preferences.contrast_more' => 'more'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredSidebar', ['choices' => ['preferences.sidebar_expanded' => 'expanded', 'preferences.sidebar_collapsed' => 'collapsed'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
            $this->addChoiceField('preferredMotion', ['choices' => ['preferences.motion_system' => 'system', 'preferences.motion_reduce' => 'reduce', 'preferences.motion_full' => 'full'], 'choice_translation_domain' => 'form', 'required' => true, 'placeholder' => false]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'enabled_locales' => ['en', 'es'], 'section' => self::SECTION_APPEARANCE]);
        $resolver->setAllowedTypes('enabled_locales', 'string[]');
        $resolver->setAllowedValues('section', [self::SECTION_APPEARANCE]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'user_preferences';
    }
}
