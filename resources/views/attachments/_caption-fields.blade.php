<label class="visually-hidden" for="attachment-caption-{{ $attachment->id }}">Caption for {{ $attachment->original_name }}</label>
<input class="form-control" id="attachment-caption-{{ $attachment->id }}" name="caption"
    maxlength="255" value="{{ $attachment->caption }}" placeholder="Add a caption">
@error('caption')
    <div class="small text-danger mt-1">{{ $message }}</div>
@enderror
