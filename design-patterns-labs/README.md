# Structural Design Patterns Labs — IPT10

Course: Integrative Programming and Technologies
Topic: Structural Design Patterns in Web Application Development (Adapter, Bridge, Composite, Decorator)
Stack: PHP 8

## How to run each lab

Each lab folder has a BAD version (`without_web.php`, the smell) and a GOOD version
(`with_pattern.php`, completed with the pattern applied). From inside a lab folder:

```
php without_web.php
php with_pattern.php
```

## Contents

| Folder | Pattern | Problem | Deliverables |
|---|---|---|---|
| `lab1_adapter/` | Adapter | Incompatible CSV/JSON/Text file writers | `with_pattern.php`, `without_web.php`, `lab1_adapter_uml.png`, `reflection.pdf` |
| `lab2_bridge/` | Bridge | Report format × data source × compression explosion | `with_pattern.php`, `without_web.php`, `data.json`, `lab2_bridge_uml.png`, `reflection.pdf` |
| `lab3_composite/` | Composite | Forum Thread/Post tree + RAG Document/Section/Chunk tree | `with_pattern.php`, `without_web.php`, `lab3_composite_uml.png`, `reflection.pdf` |
| `lab4_decorator/` | Decorator | HTTP middleware pipeline (Logging/Caching/Retry/TokenCounter) | `with_pattern.php`, `without_web.php`, `lab4_decorator_uml.png`, `reflection.pdf` |

## Extensibility proof (1 new class per lab, client code untouched)

- **Lab 1:** added `XmlLibrary` + `XmlWriterAdapter` — a 4th export format.
- **Lab 2:** added `TimesheetJsonFormatter` — a 2nd report abstraction reusing the same sources/compressors.
- **Lab 3:** added `Bundle` (forum leaf) and the full RAG variant (`Document`/`Section`/`Chunk`).
- **Lab 4:** added `RetryDecorator` and `TokenCounterDecorator` — 2 new middleware behaviors.

## References

- Gamma, E., et al. (1994). *Design Patterns: Elements of Reusable Object-Oriented Software*. Addison-Wesley — Adapter p.139, Bridge p.151, Composite p.163, Decorator p.175.
- Buschmann, F., et al. (1996). *Pattern-Oriented Software Architecture*. Wiley.
