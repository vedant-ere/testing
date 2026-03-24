import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import AppHeader from '../components/AppHeader';
import PlayerCard from '../components/PlayerCard';
import PlayersControls from '../components/PlayersControls';
import Pagination from '../components/Pagination';
import { fetchPlayersDataset } from '../services/api';
import useDebouncedValue from '../hooks/useDebouncedValue';
import {
  applyFilters,
  applySorting,
  getUniqueOptions,
  getUniqueTournamentTypes,
  paginatePlayers,
  PLAYERS_PER_PAGE,
} from '../utils/playerUtils';

const DEFAULTS = {
  page: 1,
  search: '',
  country: '',
  position: '',
  tournamentType: '',
  sortBy: 'firstName',
  sortOrder: 'asc',
};

function getActiveFilterCount(state) {
  return [state.search, state.country, state.position, state.tournamentType].filter(Boolean).length;
}

function readStateFromParams(searchParams) {
  const pageValue = Number(searchParams.get('page'));
  const page = Number.isNaN(pageValue) || pageValue < 1 ? DEFAULTS.page : pageValue;

  return {
    page,
    search: searchParams.get('search') || DEFAULTS.search,
    country: searchParams.get('country') || DEFAULTS.country,
    position: searchParams.get('position') || DEFAULTS.position,
    tournamentType: searchParams.get('tournamentType') || DEFAULTS.tournamentType,
    sortBy: searchParams.get('sortBy') || DEFAULTS.sortBy,
    sortOrder: searchParams.get('sortOrder') === 'desc' ? 'desc' : 'asc',
  };
}

function PlayersPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [players, setPlayers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const state = useMemo(() => readStateFromParams(searchParams), [searchParams]);

  const [searchInput, setSearchInput] = useState(state.search);
  const debouncedSearch = useDebouncedValue(searchInput, 400);

  useEffect(() => {
    setSearchInput(state.search);
  }, [state.search]);

  useEffect(() => {
    const shouldUpdate = debouncedSearch !== state.search;
    if (!shouldUpdate) {
      return;
    }

    const nextParams = new URLSearchParams(searchParams);

    if (debouncedSearch) {
      nextParams.set('search', debouncedSearch);
    } else {
      nextParams.delete('search');
    }

    nextParams.set('page', '1');
    setSearchParams(nextParams);
  }, [debouncedSearch, setSearchParams, searchParams, state.search]);

  useEffect(() => {
    async function loadPlayers() {
      try {
        setLoading(true);
        setError('');
        const data = await fetchPlayersDataset();
        setPlayers(data);
      } catch (apiError) {
        setError(apiError?.message || 'Failed to load players. Please try again.');
      } finally {
        setLoading(false);
      }
    }

    loadPlayers();
  }, []);

  const countries = useMemo(() => getUniqueOptions(players, 'country'), [players]);
  const positions = useMemo(() => getUniqueOptions(players, 'position'), [players]);
  const tournamentTypes = useMemo(() => getUniqueTournamentTypes(players), [players]);

  const filteredPlayers = useMemo(
    () =>
      applyFilters(players, {
        search: state.search,
        country: state.country,
        position: state.position,
        tournamentType: state.tournamentType,
      }),
    [players, state.country, state.position, state.search, state.tournamentType],
  );

  const sortedPlayers = useMemo(
    () => applySorting(filteredPlayers, state.sortBy, state.sortOrder),
    [filteredPlayers, state.sortBy, state.sortOrder],
  );

  const totalPages = Math.max(1, Math.ceil(sortedPlayers.length / PLAYERS_PER_PAGE));
  const currentPage = Math.min(state.page, totalPages);

  const paginatedPlayers = useMemo(
    () => paginatePlayers(sortedPlayers, currentPage, PLAYERS_PER_PAGE),
    [sortedPlayers, currentPage],
  );
  const activeFilterCount = getActiveFilterCount(state);

  useEffect(() => {
    if (state.page <= totalPages) {
      return;
    }

    const nextParams = new URLSearchParams(searchParams);
    nextParams.set('page', String(totalPages));
    setSearchParams(nextParams, { replace: true });
  }, [searchParams, setSearchParams, state.page, totalPages]);

  function updateParams(nextState) {
    const nextParams = new URLSearchParams();

    const merged = {
      ...state,
      ...nextState,
    };

    Object.entries(merged).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) {
        nextParams.set(key, String(value));
      }
    });

    setSearchParams(nextParams);
  }

  function handleSearchSubmit(event) {
    event.preventDefault();
    updateParams({ search: searchInput.trim(), page: 1 });
  }

  function handleClearFilters() {
    setSearchInput('');
    setSearchParams({ page: '1', sortBy: DEFAULTS.sortBy, sortOrder: DEFAULTS.sortOrder });
  }

  return (
    <>
      <AppHeader />
      <main className="page-shell">
        <header className="page-header">
          <h1>Player Directory</h1>
          <p>Find player profiles with filters, sorting and quick navigation to full details.</p>
        </header>

        <PlayersControls
          searchInput={searchInput}
          onSearchInputChange={setSearchInput}
          onSearchSubmit={handleSearchSubmit}
          country={state.country}
          onCountryChange={(country) => updateParams({ country, page: 1 })}
          countries={countries}
          position={state.position}
          onPositionChange={(position) => updateParams({ position, page: 1 })}
          positions={positions}
          tournamentType={state.tournamentType}
          onTournamentTypeChange={(tournamentType) => updateParams({ tournamentType, page: 1 })}
          tournamentTypes={tournamentTypes}
          sortBy={state.sortBy}
          onSortByChange={(sortBy) => updateParams({ sortBy, page: 1 })}
          sortOrder={state.sortOrder}
          onSortOrderToggle={() => updateParams({ sortOrder: state.sortOrder === 'asc' ? 'desc' : 'asc', page: 1 })}
          onClearFilters={handleClearFilters}
        />

        <section className="results-meta" aria-live="polite">
          <div className="meta-chip">
            <span>Total Players</span>
            <strong>{players.length}</strong>
          </div>
          <div className="meta-chip">
            <span>Matching</span>
            <strong>{sortedPlayers.length}</strong>
          </div>
          <div className="meta-chip">
            <span>Page</span>
            <strong>
              {currentPage} / {totalPages}
            </strong>
          </div>
          <div className="meta-chip">
            <span>Active Filters</span>
            <strong>{activeFilterCount}</strong>
          </div>
        </section>

        {loading && (
          <section className="players-grid" aria-label="Loading players">
            {Array.from({ length: 6 }).map((_, index) => (
              <div key={`skeleton-${index}`} className="player-card skeleton-card" />
            ))}
          </section>
        )}
        {!loading && error && <p className="state-text error">{error}</p>}
        {!loading && !error && paginatedPlayers.length === 0 && (
          <p className="state-text">No players found for the selected filters.</p>
        )}

        {!loading && !error && paginatedPlayers.length > 0 && (
          <>
            <section className="players-grid" aria-label="Players list">
              {paginatedPlayers.map((player) => (
                <PlayerCard key={player.id} player={player} />
              ))}
            </section>

            <Pagination
              currentPage={currentPage}
              totalPages={totalPages}
              onPageChange={(page) => updateParams({ page })}
            />
          </>
        )}
      </main>
    </>
  );
}

export default PlayersPage;
