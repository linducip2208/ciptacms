@extends('admin.layout')
@section('title','Users')@section('crumb','Users')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.users.store') }}" class="flex flex-wrap gap-2">@csrf<input name="name" required placeholder="Name" class="input !w-40"><input name="email" type="email" required placeholder="Email" class="input !w-52"><input name="password" required placeholder="Password" class="input !w-40"><button class="btn-primary">+ User</button></form></div>
<div class="card p-4"><form class="mb-3"><input name="search" value="{{ request('search') }}" class="input" placeholder="Search…"></form><table class="tbl"><thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>@foreach($u->roles as $r)<span class="badge">{{ $r->slug }}</span> @endforeach</td><td>{{ $u->is_active?'active':'inactive' }}</td>
<td><form method="POST" action="{{ route('admin.users.toggle',$u) }}" class="inline">@csrf<button class="text-indigo-600 text-sm">Toggle</button></form></td></tr>@endforeach</tbody></table><div class="mt-2">{{ $users->links() }}</div></div>
@endsection
