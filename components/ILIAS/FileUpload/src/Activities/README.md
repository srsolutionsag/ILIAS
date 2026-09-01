# Files in Activities

How an activity takes a file, and why it takes it the way it does.

## The starting point

An [Activity](../../../Component/src/Activities/README.md) describes its input as a `FormInput` of
the UI framework, so that the same description serves a form, a webservice and a workflow tool.

Forms, however, never carry the bytes of a file. `Field\File` takes an `UploadHandler`, the browser
sends the file to `$handler->getUploadURL()`, and what ends up in the form is an *identifier* of
whatever that endpoint made of the upload - in the ILIAS handlers the serialised `rid` of a resource:

```php
// AbstractCtrlAwareIRSSUploadHandler::getUploadResult()
$identifier = $this->irss->manage()->upload($result, $this->stakeholder)->serialize();
```

So a form always uploads in two steps, and the first step is an endpoint of its own.

## Why activities do not do that

An upload endpoint accepts files without knowing what they are for. Every call creates something,
and nothing forces a second call to ever use it. In a GUI that is bounded by the form the user is
looking at. Over a webservice it is not: anyone with a session can fill the storage, and afterwards
nobody can tell an abandoned upload from one that is still waited for.

Therefore **there is no upload endpoint, and no upload activity**. The file travels inside the call
that uses it. `CreateFile` either creates the object with its content or leaves nothing behind, and
the permission is checked before a single byte is stored.

What that costs: a file has to fit into one request - the limits of `post_max_size` and of the
memory apply - and there is neither chunking nor a resumable upload. For very large files this is
the wrong door; that case would need an endpoint bound to a concrete target, not a free store.

## The wire format

The content is **base64** in a plain parameter:

* JSON has no type for bytes, so a REST client has to encode anyway.
* SOAP encodes binary content the same way (`xsd:base64Binary`).
* Anything that can carry text can carry this, and there is nothing to negotiate.

The REST service adds the convenience an HTTP client expects and encodes a `multipart/form-data`
part before the activity sees it, so this works as well:

```bash
curl -b cookies -F 'content=@notes.pdf' -F 'parent_ref_id=1' http://.../rest/file/create
```

Delivery is the mirror image: base64 in the answer, raw bytes with `?raw=1`.

## How an activity takes a file

`FileParameter` describes the parameters and reads them, `FileContent` is what an activity works
with. Both are provided by the FileUpload component; an activity gets the parameter injected.

| Parameter | Meaning |
| --- | --- |
| `filename` | name of the file including its suffix |
| `content` | the content of the file, base64 encoded |

```php
public function __construct(
    private readonly FileParameter $file,
    private readonly RepositoryProvider $repository,
) {
}

public function getInputDescription(FieldFactory $f): FormInput
{
    return $f->section([
        'parent_ref_id' => $f->numeric('Target', '...')->withRequired(true)->withDedicatedName('parent_ref_id'),
        ...$this->file->describe($f, 'File', 'The content of the new file'),
    ], 'Create a file');
}

public function maybePerformAs(InputFactory $input_factory, int $usr_id, array $raw_parameters): Result
{
    // ... convert the other parameters, then:
    if (!$this->isAllowedToPerform($usr_id, $parameters)) {
        return $this->data->error(new \DomainException('You are not allowed to create files here.', 403));
    }

    // only now, and never earlier
    $file = $this->file->read($raw_parameters);

    return $this->data->ok($this->perform([...$parameters, 'file' => $file]));
}

public function perform(mixed $parameters): FileEntry
{
    $file = $parameters['file'];

    try {
        return $this->repository->createFile(..., $file);
    } finally {
        $file->release();
    }
}
```

Three rules, and they are the whole contract:

1. **Read the file after the permission check.** A caller who may not put something there does not
   get to put bytes into the installation either.
2. **Release it, always.** `perform()` wraps its work in `try`/`finally`; the content is scratch,
   not a result.
3. **Never hand out a handle.** Nothing that addresses the content leaves the activity, so nothing
   outside can refer to it later.

### Limits

An activity can say what it accepts, the way the file field of the UI framework does. A
`FileParameter` is immutable, so the narrowed one has to be kept - usually right where it is
injected:

```php
public function __construct(FileParameter $file, private readonly RepositoryProvider $repository)
{
    $this->file = $file
        ->withMaxFileSize(10 * 1024 * 1024)
        ->withAcceptedMimeTypes(['application/pdf']);
}
```

Both end up in the description of the parameter - and therefore in the generated documentation - and
are enforced when it is read. A file that is too large is refused before it is decoded, a file of an
unwanted type is released again.

### Two files in one activity

`withNames()` gives the pair of parameters different names, so an activity can take more than one
file:

```php
$attachment = $this->file->withNames('attachment', 'attachment_name');
```

## Why the content still ends up in a file

A stream over a string has no path, and both the ResourceStorage and `ilObjFile` take the name of
what they store from the file behind the stream. So `FileParameter::read()` writes the content into
the temp filesystem (`TempFileStore`) and `FileContent` reads its stream from there.

That store is scratch space for the duration of the request. Its handle stays inside the activity,
and `release()` removes the file and its folder again.

## What this is deliberately not

* **Not a `Field\File`.** That field presupposes the separate endpoint this design does without. The
  price is that the input description here is two ordinary fields and that the limits are enforced
  by `FileParameter` instead of by the framework.
* **Not a `rid` parameter.** A resource identification is not a capability, and a client that could
  name one would be naming something it does not own. See
  `ILIAS\ResourceStorage\Activities\DeliverResource` for the two doors that follow from this.

## Open towards upstream

The UI framework has no way to describe content that arrives *with* the call - `Field\File` always
means "the bytes are already somewhere else". An input for inline content, with the same limits a
file field carries, would let these activities describe themselves properly and would make a GUI
form for them possible. Until then this is what `FileParameter` fills in.
