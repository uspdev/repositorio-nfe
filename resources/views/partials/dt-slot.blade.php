<form method="GET" class="form-inline">
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle ml-2" type="button" data-toggle="dropdown"
      title="Muda o ano base">
      <i class="far fa-calendar fa-lg"></i>&nbsp; {{ $anoCtl['atual'] }} <span class="caret"></span>
    </button>
    <div class="dropdown-menu" aria-labelledby="dropdownAno">
      @foreach ($anoCtl['anos'] as $ano)
        <button class="dropdown-item {{ $anoCtl['atual'] == $ano ? 'active' : '' }}" type="submit" name="ano"
          value="{{ $ano }}">
          {{ $ano }}
        </button>
      @endforeach
    </div>
  </div>
</form>
