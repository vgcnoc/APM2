<?php
$schedules = \App\Models\TechnicianSchedule::where('type', 'installation')->get();
$count = 0;
foreach ($schedules as $s) {
    if ($s->notes) {
        $lines = explode("\n", $s->notes);
        $changed = false;
        foreach ($lines as &$line) {
            if (str_starts_with($line, ' - ')) {
                $line = substr($line, 3);
                $parts = explode(' : ', $line);
                if (count($parts) == 2) {
                    $line = 'Material: ' . trim($parts[0]) . ' (' . trim($parts[1]) . ')';
                    $changed = true;
                }
            }
            if (str_starts_with($line, 'ONT: ') && strpos($line, ' null ') !== false) {
                $line = str_replace(' null ', ' ', $line);
                $changed = true;
            }
        }
        if ($changed) {
            $s->notes = implode("\n", $lines);
            $s->save();
            $count++;
        }
    }
}
echo "Fixed $count schedules\n";
