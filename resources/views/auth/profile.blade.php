@extends("layout")

@section("title")
Modifier le profil
@endsection

@section("content")

    @if (count($errors))
    <div class="mb-3">
        <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" role="form" enctype="multipart/form-data">
        @csrf                
        <ul class="list-unstyled col-lg-6">
            <li class="mb-3">
                <label class="form-label">Name</label>
                <input id="name" type="text" class="form-control" name="name" value="{{ old('name', auth()->user()->name) }}"  disabled>
            </li>
            <li class="mb-3">
                <label class="form-label">Title</label>
                <input id="name" type="text" class="form-control" name="Title" value="{{ old('title', auth()->user()->title) }}"  disabled>
            </li>
            <li class="mb-3">
                <label class="form-label">Email</label>                
                <input id="email" type="text" class="form-control" name="email" value="{{ old('email', auth()->user()->email) }}" disabled>
            </li>
            <li class="mb-3">
                <label class="form-label">Profile Image</label><br>
                    @if (auth()->user()->profile_image)
                        <img src="{{asset('/storage/avatar/'.Auth::user()->id)}}" style="width: 100px; height: 100px; border-radius: 50%;">
                    @else
                        <img src="/images/user.jpeg" style="width: 100px; height: 100px; border-radius: 50%;">
                    @endif
                    <div class="input-group mt-2">
                        <span class="input-group-text">Select your photo</span>
                        <input class="form-control" type="file" name="profile_image">
                    </div>
            </li>
        </ul>
        <button type="submit" class="btn btn-success"><i class="bi bi-floppy"></i> Sauver</button>
        <button type="submit" class="btn btn-light border" data-form-method="GET" data-form-action="/"><span class="bi bi-x-circle"></span> Annuler</button>
    </form>

@endsection
