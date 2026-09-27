@extends('admin.layout')
@section('title','Users')@section('crumb','Users')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.users.store') }}" class="d-flex flex-wrap gap-2">@csrf<input name="name" required placeholder="Name" class="form-control !w-40"><input name="email" type="email" required placeholder="Email" class="form-control !w-52"><input name="password" required placeholder="Password" class="form-control !w-40"><button class="btn btn-primary">+ User</button></form></div>
<div class="card"><form class="mb-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search…"></form><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>@foreach($u->roles as $r)<span class="badge">{{ $r->slug }}</span> @endforeach</td><td>{{ $u->is_active?'active':'inactive' }}</td>
<td><form method="POST" action="{{ route('admin.users.toggle',$u) }}" class="inline">@csrf<button class="text-indigo-600 text-sm">Toggle</button></form></td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $users->total(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 5]),
    'icon' => 'ti-users',
    'title' => 'No users found',
    'message' => 'No account matches this list. Create one with the form above, or clear the search box.',
])
</tbody></table></div><div class="mt-2">{{ $users->links() }}</div></div>
@endsection
