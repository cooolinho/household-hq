<?php

namespace Tests\Unit;

use App\Services\NotificationTemplateService;
use Tests\TestCase;

class NotificationTemplateServiceTest extends TestCase
{
    public function test_it_replaces_placeholders_in_template_text(): void
    {
        $service = new NotificationTemplateService();

        $result = $service->render(
            template: 'Hallo {{user_name}}, Vertrag {{insurance_number}}',
            placeholders: [
                'user_name' => 'Max Mustermann',
                'insurance_number' => 'V-123',
            ],
        );

        $this->assertSame('Hallo Max Mustermann, Vertrag V-123', $result);
    }
}

