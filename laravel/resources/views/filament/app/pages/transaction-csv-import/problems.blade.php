{{-- Fehlerliste eines ImportReport ($report) --}}
@foreach ($report->problemSummaries() as $problem)
    <div>
        <p class="font-medium">{{ $problem['summary'] }}</p>
        <p class="text-xs">
            Zeilen: {{ implode(', ', $problem['lines']) }}{{ $problem['more'] > 0 ? ' und ' . $problem['more'] . ' weitere' : '' }}
        </p>
    </div>
@endforeach

@if ($report->errors !== [])
    <details class="mt-2">
        <summary class="cursor-pointer text-xs font-medium">Details je Zeile anzeigen</summary>
        <div class="mt-2 max-h-64 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <tbody>
                    @foreach ($report->errors as $error)
                        <tr>
                            <td class="py-0.5 pr-3 align-top">Zeile {{ $error['line'] }}</td>
                            <td class="py-0.5">{{ $error['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($report->invalid > count($report->errors))
                <p class="mt-1 text-xs">Es werden nur die ersten {{ count($report->errors) }} Fehler angezeigt.</p>
            @endif
        </div>
    </details>
@endif
