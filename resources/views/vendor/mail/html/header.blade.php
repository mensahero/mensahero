@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{asset('/favicon.svg')}}" class="logo" style="width: 35px; height: 35px" alt="Mensahero Logo"> {{config('app.name')}}
</a>
</td>
</tr>
