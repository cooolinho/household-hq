<?php

namespace App\Services;

use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
use App\Models\NotificationTemplate;

class NotificationTemplateService
{
    const string KEY_EMAIL_SUBJECT = 'insurance_move.email.subject';
    const string KEY_EMAIL_BODY = 'insurance_move.email.body';
    const string KEY_LETTER_SUBJECT = 'insurance_move.letter.subject';
    const string KEY_LETTER_BODY = 'insurance_move.letter.body';

    public function resetMoveNotificationTemplatesToDefaults(): void
    {
        $this->saveMoveNotificationTemplates($this->defaults());
    }

    /**
     * @param array<string, string> $values
     */
    public function saveMoveNotificationTemplates(array $values): void
    {
        foreach ([
                     self::KEY_EMAIL_SUBJECT,
                     self::KEY_EMAIL_BODY,
                     self::KEY_LETTER_SUBJECT,
                     self::KEY_LETTER_BODY,
                 ] as $key) {
            NotificationTemplate::query()->updateOrCreate(
                [NotificationTemplate::template_key => $key],
                [NotificationTemplate::value => (string)($values[$key] ?? '')],
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function defaults(): array
    {
        return [
            self::KEY_EMAIL_SUBJECT => 'Adressaenderung nach Umzug - {{insurance_name}}',
            self::KEY_EMAIL_BODY => implode("\n", [
                'Sehr geehrte Damen und Herren,',
                '',
                'ich bitte Sie, meine Adresse in den Vertragsdaten zu aktualisieren.',
                '',
                'Alte Adresse:',
                '{{old_address_line1}}',
                '{{old_address_line2}}',
                '{{old_address_zip}} {{old_address_city}}',
                '',
                'Neue Adresse:',
                '{{new_address_line1}}',
                '{{new_address_line2}}',
                '{{new_address_zip}} {{new_address_city}}',
                '',
                'Versicherung: {{insurance_name}}',
                'Versicherungsnummer: {{insurance_number}}',
                '',
                'Ich bitte um kurze Bestaetigung der Aenderung.',
                '',
                'Vielen Dank',
                '{{user_name}}',
            ]),
            self::KEY_LETTER_SUBJECT => 'Adressaenderung nach Umzug',
            self::KEY_LETTER_BODY => implode("\n", [
                'Sehr geehrte Damen und Herren,',
                '',
                'hiermit teile ich Ihnen meine neue Adresse mit und bitte um Aktualisierung meiner Vertragsdaten.',
                '',
                'Alte Adresse:',
                '{{old_address_line1}}',
                '{{old_address_line2}}',
                '{{old_address_zip}} {{old_address_city}}',
                '',
                'Neue Adresse:',
                '{{new_address_line1}}',
                '{{new_address_line2}}',
                '{{new_address_zip}} {{new_address_city}}',
                '',
                'Versicherung: {{insurance_name}}',
                'Versicherungsnummer: {{insurance_number}}',
                '',
                'Ich bitte um kurze Bestaetigung der Aenderung.',
                '',
                'Mit freundlichen Gruessen',
                '{{user_name}}',
            ]),
        ];
    }

    /**
     * @param array<string, string> $placeholders
     */
    public function render(string $template, array $placeholders): string
    {
        $replacements = [];

        foreach ($placeholders as $placeholder => $value) {
            $replacements['{{' . $placeholder . '}}'] = $value;
        }

        return strtr($template, $replacements);
    }

    /**
     * @return array<int, string>
     */
    public function availablePlaceholders(): array
    {
        return [
            'user_name',
            'insurance_name',
            'insurance_company',
            'insurance_number',
            'old_address_line1',
            'old_address_line2',
            'old_address_zip',
            'old_address_city',
            'new_address_line1',
            'new_address_line2',
            'new_address_zip',
            'new_address_city',
            'current_date',
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function getDraftTemplateForChannel(string $channel): array
    {
        $templates = $this->getMoveNotificationTemplates();

        if ($channel === InsuranceMoveNotificationChannelEnum::BRIEF->name) {
            return $templates['letter'];
        }

        return $templates['email'];
    }

    /**
     * @return array<string, array{subject: string, body: string}>
     */
    public function getMoveNotificationTemplates(): array
    {
        return [
            'email' => [
                'subject' => $this->getValue(self::KEY_EMAIL_SUBJECT, $this->defaults()[self::KEY_EMAIL_SUBJECT]),
                'body' => $this->getValue(self::KEY_EMAIL_BODY, $this->defaults()[self::KEY_EMAIL_BODY]),
            ],
            'letter' => [
                'subject' => $this->getValue(self::KEY_LETTER_SUBJECT, $this->defaults()[self::KEY_LETTER_SUBJECT]),
                'body' => $this->getValue(self::KEY_LETTER_BODY, $this->defaults()[self::KEY_LETTER_BODY]),
            ],
        ];
    }

    private function getValue(string $key, string $fallback): string
    {
        $value = NotificationTemplate::query()
            ->where(NotificationTemplate::template_key, $key)
            ->value(NotificationTemplate::value);

        if (!is_string($value) || trim($value) === '') {
            return $fallback;
        }

        return $value;
    }
}

