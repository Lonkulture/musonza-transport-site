@extends('layout')
@section('title','Our expertise — Musonza')
@section('content')
<section class="page-head container"><p class="eyebrow">WHAT WE DO</p><h1>Connected expertise.<br><em>Practical outcomes.</em></h1><p class="lead">Five disciplines that help businesses move goods, navigate challenges and build long-term value.</p></section>
<section class="container services-list">@foreach(config('company.services') as $slug=>$service)<article><img src="/assets/{{ $service['image'] }}" alt="{{ $service['title'] }}" loading="lazy"><div><p class="eyebrow">0{{ $loop->iteration }} / OUR EXPERTISE</p><h2>{{ $service['title'] }}</h2><p>{{ $service['text'] }}</p><a class="line-link" href="/services/{{ $slug }}">Explore this service <span>↗</span></a></div></article>@endforeach</section>
<section class="container cta section-pad"><h2>Where can we help?<span>↗</span></h2><a href="/request/quote" class="button">Discuss your requirements <span>↗</span></a></section>
@endsection
