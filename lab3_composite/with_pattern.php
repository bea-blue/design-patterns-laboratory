<?php
// Lab 3 - WITH Composite (GOOD - completed)
// Primary: Forum Thread/Post. Second variant: RAG Document/Section/Chunk (proves the pattern transfers).

// =====================================================================
// Variant 1: Forum Thread / Post
// =====================================================================
interface ForumComponent
{
    public function display(int $depth = 0): void;
}

class Post implements ForumComponent
{
    public function __construct(private string $author, private string $message) {}

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}

class Thread implements ForumComponent
{
    /** @var ForumComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(ForumComponent $c): void
    {
        $this->children[] = $c;
    }

    public function display(int $depth = 0): void
    {
        // Missing 1 (completed): recurse into every child at depth+1, no instanceof needed.
        echo str_repeat("  ", $depth) . "+ Thread: {$this->title}\n";
        foreach ($this->children as $child) {
            $child->display($depth + 1);
        }
    }

    public static function fromApi(int $postId): self
    {
        $postJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId");
        $post = json_decode($postJson, true);
        $thread = new self($post["title"]);
        $thread->add(new Post("Author {$post['userId']}", substr($post["body"], 0, 40) . "..."));

        $commentsJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId/comments");
        $comments = json_decode($commentsJson, true);
        $replies = new Thread("Replies");
        foreach (array_slice($comments, 0, 2) as $c) {
            $replies->add(new Post($c["email"], substr($c["body"], 0, 30) . "..."));
        }
        $thread->add($replies);
        return $thread;
    }
}

// Missing 3 (completed): a new leaf added with 1 class - no client (display loop) changes.
class Bundle implements ForumComponent
{
    public function __construct(private string $label, private array $tags = []) {}

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        echo $indent . "- Bundle [{$this->label}]: " . implode(', ', $this->tags) . "\n";
    }
}

// =====================================================================
// Missing 2 (completed): RAG variant - Document -> Section -> Chunk
// Same shape as Thread/Post, renamed for a retrieval-augmented-generation document tree.
// =====================================================================
interface TextComponent
{
    public function getText(): string;
    public function embed(): array;
}

class Chunk implements TextComponent
{
    public function __construct(private string $text) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function embed(): array
    {
        // Toy "embedding": a fixed-length numeric vector derived from character codes.
        $vector = [];
        for ($i = 0; $i < 4; $i++) {
            $vector[] = isset($this->text[$i]) ? ord($this->text[$i]) : 0;
        }
        return $vector;
    }
}

class Section implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $heading) {}

    public function add(TextComponent $c): void
    {
        $this->children[] = $c;
    }

    public function getText(): string
    {
        $text = "## {$this->heading}\n";
        foreach ($this->children as $child) {
            $text .= $child->getText() . "\n";
        }
        return $text;
    }

    public function embed(): array
    {
        $sum = [0, 0, 0, 0];
        foreach ($this->children as $child) {
            foreach ($child->embed() as $i => $v) {
                $sum[$i] += $v;
            }
        }
        return $sum;
    }
}

class Document implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(TextComponent $c): void
    {
        $this->children[] = $c;
    }

    public function getText(): string
    {
        $text = "# {$this->title}\n";
        foreach ($this->children as $child) {
            $text .= $child->getText();
        }
        return $text;
    }

    public function embed(): array
    {
        $sum = [0, 0, 0, 0];
        foreach ($this->children as $child) {
            foreach ($child->embed() as $i => $v) {
                $sum[$i] += $v;
            }
        }
        return $sum;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Composite (GOOD - completed):\n\n";

    echo "-- Forum tree (live API) --\n";
    $thread = Thread::fromApi(1);
    $thread->add(new Bundle("starter-pack", ["welcome", "faq"]));
    $thread->display();

    echo "\n-- RAG document tree (Document -> Section -> Chunk) --\n";
    $doc = new Document("Attendance API Guide");
    $intro = new Section("Introduction");
    $intro->add(new Chunk("This guide explains the attendance endpoints."));
    $usage = new Section("Usage");
    $usage->add(new Chunk("Call GET /times to list entries."));
    $usage->add(new Chunk("Call POST /times to log a new entry."));
    $doc->add($intro);
    $doc->add($usage);

    echo $doc->getText();
    echo "  Document embed (sum of chunk vectors): [" . implode(', ', $doc->embed()) . "]\n";

    echo "\n  Same ForumComponent/TextComponent interface for leaf and composite - no instanceof needed anywhere.\n";
}
