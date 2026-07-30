<?php

namespace Tests\Unit;

use App\Models\Financial\Insurance;
use App\Models\User;
use App\Services\InsuranceMoveNotificationService;
use App\Services\NotificationTemplateService;
use Tests\TestCase;

class InsuranceMoveNotificationServiceTest extends TestCase
{
    public function test_it_builds_the_new_address_from_user_profile_fields(): void
    {
        $service = new InsuranceMoveNotificationService($this->makeTemplateServiceStub());

        $user = new User([
            User::address_street => 'Musterstrasse',
            User::address_street_number => '7a',
            User::address_zip => '12345',
            User::address_city => 'Berlin',
        ]);

        $newAddress = $service->newAddressFromUser($user);

        $this->assertSame('Musterstrasse 7a', $newAddress['line1']);
        $this->assertSame('12345', $newAddress['zip']);
        $this->assertSame('Berlin', $newAddress['city']);
    }

    public function test_it_builds_a_reusable_move_notification_draft(): void
    {
        $service = new InsuranceMoveNotificationService($this->makeTemplateServiceStub());

        $insurance = new Insurance([
            Insurance::name => 'Hausrat',
            Insurance::number => 'V-1234',
        ]);

        $draft = $service->buildDraft(
            insurance: $insurance,
            oldAddress: [
                'line1' => 'Alte Strasse 1',
                'line2' => '',
                'zip' => '11111',
                'city' => 'Hamburg',
            ],
            newAddress: [
                'line1' => 'Neue Strasse 9',
                'line2' => '',
                'zip' => '22222',
                'city' => 'Muenchen',
            ],
            channel: 'EMAIL',
        );

        $this->assertStringContainsString('Adressaenderung', $draft['subject']);
        $this->assertStringContainsString('Alte Adresse:', $draft['body']);
        $this->assertStringContainsString('Neue Strasse 9', $draft['body']);
        $this->assertStringContainsString('Versicherungsnummer: V-1234', $draft['body']);
    }

    private function makeTemplateServiceStub(): NotificationTemplateService
    {
        return new class extends NotificationTemplateService {
            public function getDraftTemplateForChannel(string $channel): array
            {
                return [
                    'subject' => 'Adressaenderung - {{insurance_name}}',
                    'body' => "Alte Adresse:\n{{old_address_line1}}\nNeue Adresse:\n{{new_address_line1}}\nVersicherungsnummer: {{insurance_number}}",
                ];
            }
        };
    }
}

