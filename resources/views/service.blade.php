@extends('layout')
@section('title',$service['title'].' — Musonza')
@section('description',$service['text'])
@section('content')
<section class="service-hero"><img src="/assets/{{ $service['image'] }}" alt="{{ $service['title'] }}"><div class="container"><a class="back-light" href="/services">← All expertise</a><p class="eyebrow light">MUSONZA / OUR EXPERTISE</p><h1>{{ $service['title'] }}</h1><p>{{ $service['short'] }}</p></div></section>
<section class="container section-pad detail-grid"><div><p class="eyebrow">A PRACTICAL PARTNER</p><h2>Built around<br>your requirements.</h2><p class="lead">{{ $service['text'] }}</p><p>Our team begins by understanding your scope, constraints and objectives, then works with you to agree a suitable approach.</p>@if($slug==='investments')<p class="caption">Investment enquiries are partnership discussions. No investment product, guaranteed return or public offer is presented on this website.</p>@endif</div><div class="scope-panel"><h3>How we can support you</h3><ul>@foreach($service['points'] as $point)<li><span>↗</span>{{ $point }}</li>@endforeach</ul><a class="button" href="/request/quote?service={{ $slug }}">Request a quote <span>↗</span></a><a class="line-link" href="/request/consultation?service={{ $slug }}">Book a consultation <span>→</span></a></div></section>
@endsection
