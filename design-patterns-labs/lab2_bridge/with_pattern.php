<?php
// Lab 2 - WITH Bridge (GOOD - completed)
// Times formatter (abstraction) x DataSource + Compressor (implementors) - compression Bridge.
// Swap the source or the compressor freely without touching the report logic.

// ---- Implementor A: where records come from ----
interface DataSource
{
    public function fetch(): array;
}

class ApiDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        if ($json === false || trim($json) === "") {
            throw new RuntimeException("Live API unreachable.");
        }
        return json_decode($json, true);
    }
}

class FileDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents(__DIR__ . "/data.json");
        if ($json === false) {
            throw new RuntimeException("data.json not found.");
        }
        return json_decode($json, true);
    }
}

// ---- Implementor B: how output is compressed ----
interface Compressor
{
    public function compress(string $content): string;
    public function isCompressed(): bool;
}

class GzipCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return gzencode($content, 6);
    }

    public function isCompressed(): bool
    {
        return true;
    }
}

class NoneCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return $content;
    }

    public function isCompressed(): bool
    {
        return false;
    }
}

// ---- Abstraction: the report, delegates both varying dimensions ----
abstract class TimesFormatter
{
    public function __construct(
        protected DataSource $source,
        protected Compressor $compressor
    ) {}

    // Missing 1 (completed): swap the compressor implementor at runtime.
    public function setCompressor(Compressor $compressor): void
    {
        $this->compressor = $compressor;
    }

    abstract protected function toText(array $records): string;

    public function generate(string $title): string
    {
        $records = $this->source->fetch();
        $body = $this->toText($records);
        return $this->compressor->compress($title . "\n" . $body);
    }
}

class AttendanceTimesFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        $lines = [];
        foreach ($records as $r) {
            $lines[] = "{$r['userId']} {$r['id']} " . substr($r['title'] ?? '', 0, 18);
        }
        return implode("\n", $lines);
    }
}

// Missing 2 (completed): second abstraction leaf, proving the two hierarchies vary independently.
class TimesheetJsonFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        return json_encode($records, JSON_PRETTY_PRINT);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Bridge (GOOD - completed):\n";

    // Missing 3: demo 2 formats x 2 sources x 2 compressors = 8 combos, using 6 classes total.
    $formatters = [
        'AttendanceTimesFormatter + ApiDataSource'  => new AttendanceTimesFormatter(new ApiDataSource(), new NoneCompressor()),
        'AttendanceTimesFormatter + FileDataSource' => new AttendanceTimesFormatter(new FileDataSource(), new NoneCompressor()),
        'TimesheetJsonFormatter + ApiDataSource'    => new TimesheetJsonFormatter(new ApiDataSource(), new NoneCompressor()),
        'TimesheetJsonFormatter + FileDataSource'   => new TimesheetJsonFormatter(new FileDataSource(), new NoneCompressor()),
    ];

    foreach ($formatters as $label => $report) {
        $plain = $report->generate("TIMES");
        printf("  [%s] plain size=%d bytes\n", $label, strlen($plain));

        $report->setCompressor(new GzipCompressor());
        $gzipped = $report->generate("TIMES");
        printf("  [%s] gzip  size=%d bytes (smaller: %s)\n", $label, strlen($gzipped), strlen($gzipped) < strlen($plain) ? 'yes' : 'no');
    }

    echo "  4 formatter x source combos shown above, each swappable with gzip/none -> 8 combos, 6 classes total.\n";
}
