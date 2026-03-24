const API_KEY = import.meta.env.VITE_SPORTMONKS_API_KEY;
const RAW_BASE_URL = import.meta.env.VITE_SPORTMONKS_API_BASE_URL || '/api/sportmonks';
const BASE_URL = RAW_BASE_URL.includes('cricket.sportmonks.com') ? '/api/sportmonks' : RAW_BASE_URL;

const REQUEST_TIMEOUT_MS = 120000;
const RETRY_ATTEMPTS = 2;

if (!API_KEY) {
  console.warn('Missing VITE_SPORTMONKS_API_KEY. Add it in .env file.');
}

function sleep(ms) {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
}

function withTimeout(signal, timeoutMs) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => {
    controller.abort('Request timed out');
  }, timeoutMs);

  if (signal) {
    signal.addEventListener('abort', () => controller.abort(), { once: true });
  }

  return {
    signal: controller.signal,
    clear: () => clearTimeout(timeoutId),
  };
}

async function fetchWithRetry(url, options = {}, attempts = RETRY_ATTEMPTS) {
  let lastError = null;

  for (let currentAttempt = 1; currentAttempt <= attempts; currentAttempt += 1) {
    const timeoutController = withTimeout(options.signal, REQUEST_TIMEOUT_MS);

    try {
      const response = await fetch(url, {
        ...options,
        signal: timeoutController.signal,
      });

      timeoutController.clear();

      if (!response.ok) {
        const errorBody = await response.text();
        throw new Error(`API request failed (${response.status}): ${errorBody || response.statusText}`);
      }

      return response.json();
    } catch (error) {
      timeoutController.clear();
      lastError = error;

      if (currentAttempt < attempts) {
        await sleep(500 * currentAttempt);
      }
    }
  }

  if (lastError?.name === 'AbortError') {
    throw new Error('Request timed out. Please retry.');
  }

  throw lastError || new Error('Failed to fetch data from API.');
}

function buildUrl(path, query = {}) {
  if (!API_KEY) {
    throw new Error('Missing VITE_SPORTMONKS_API_KEY in .env');
  }

  const targetPath = `${BASE_URL}${path}`;
  const url = BASE_URL.startsWith('http') ? new URL(targetPath) : new URL(targetPath, window.location.origin);

  url.searchParams.set('api_token', API_KEY);

  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, String(value));
    }
  });

  return url.toString();
}

function toNumber(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function getValueAtPath(source, path) {
  return path.split('.').reduce((value, part) => {
    if (value && typeof value === 'object') {
      return value[part];
    }

    return undefined;
  }, source);
}

function getFirstValue(source, paths) {
  for (const path of paths) {
    const value = getValueAtPath(source, path);

    if (value !== undefined && value !== null && value !== '') {
      return value;
    }
  }

  return null;
}

function normalizeCareerType(rawType) {
  if (!rawType) {
    return 'Other';
  }

  const text = String(rawType).trim();
  const upperText = text.toUpperCase();

  if (upperText.includes('ODI')) {
    return 'ODI';
  }

  if (upperText.includes('T20')) {
    return 'T20';
  }

  if (upperText.includes('TEST')) {
    return 'Test';
  }

  return text;
}

function extractTournamentTypesFromCareer(careerData) {
  const career = Array.isArray(careerData) ? careerData : careerData?.data || [];

  return [
    ...new Set(
      career
        .map((record) => {
          const rawType =
            record.type ||
            record.tournament_type ||
            record.tournament?.type ||
            record.season?.type ||
            record.league?.type;

          return rawType ? String(rawType).trim() : null;
        })
        .filter(Boolean),
    ),
  ];
}

function extractCareerStats(careerData) {
  const records = Array.isArray(careerData) ? careerData : careerData?.data || [];
  const groupedStats = new Map();

  records.forEach((record) => {
    const statType = normalizeCareerType(
      getFirstValue(record, ['type', 'tournament_type', 'season.type', 'season.name', 'league.code', 'league.name']),
    );

    const matches = toNumber(
      getFirstValue(record, ['matches', 'games', 'fixtures', 'played', 'batting.matches', 'bowling.matches']),
    );

    const runs = toNumber(
      getFirstValue(record, ['runs', 'runs_scored', 'batting.runs', 'batting.runs_scored', 'batting.total_runs']),
    );

    const wickets = toNumber(getFirstValue(record, ['wickets', 'wickets_taken', 'bowling.wickets', 'bowling.wickets_taken']));
    const average = toNumber(getFirstValue(record, ['average', 'avg', 'batting.average', 'batting.avg', 'bowling.average']));

    if (!groupedStats.has(statType)) {
      groupedStats.set(statType, {
        type: statType,
        matches: null,
        runs: null,
        wickets: null,
        average: null,
      });
    }

    const currentStats = groupedStats.get(statType);

    if (matches !== null) {
      currentStats.matches = (currentStats.matches || 0) + matches;
    }

    if (runs !== null) {
      currentStats.runs = (currentStats.runs || 0) + runs;
    }

    if (wickets !== null) {
      currentStats.wickets = (currentStats.wickets || 0) + wickets;
    }

    if (currentStats.average === null && average !== null) {
      currentStats.average = average;
    }
  });

  const preferredOrder = ['ODI', 'T20', 'Test'];

  return [...groupedStats.values()].sort((first, second) => {
    const firstOrder = preferredOrder.indexOf(first.type);
    const secondOrder = preferredOrder.indexOf(second.type);
    const safeFirstOrder = firstOrder === -1 ? Number.MAX_SAFE_INTEGER : firstOrder;
    const safeSecondOrder = secondOrder === -1 ? Number.MAX_SAFE_INTEGER : secondOrder;

    if (safeFirstOrder === safeSecondOrder) {
      return String(first.type).localeCompare(String(second.type));
    }

    return safeFirstOrder - safeSecondOrder;
  });
}

function extractTeamNames(teamsData) {
  const teams = Array.isArray(teamsData) ? teamsData : teamsData?.data || [];

  return [...new Set(teams.map((team) => team?.name).filter(Boolean))];
}

async function fetchCountryMap(countryIds = []) {
  const uniqueCountryIds = [
    ...new Set(countryIds.map((countryId) => Number(countryId)).filter((countryId) => Number.isInteger(countryId) && countryId > 0)),
  ];

  const countryNameById = new Map();

  if (uniqueCountryIds.length === 0) {
    return countryNameById;
  }

  try {
    const payload = await fetchWithRetry(buildUrl('/countries'));
    const countries = payload?.data || [];

    if (Array.isArray(countries)) {
      countries.forEach((country) => {
        const countryId = Number(country?.id);

        if (Number.isInteger(countryId) && country?.name) {
          countryNameById.set(countryId, country.name);
        }
      });
    }
  } catch (error) {
    console.warn('Unable to resolve countries list.', error);
  }

  return countryNameById;
}

async function fetchPositionMap() {
  const positionNameById = new Map();

  try {
    const payload = await fetchWithRetry(buildUrl('/positions'));
    const positions = payload?.data || [];

    if (Array.isArray(positions)) {
      positions.forEach((position) => {
        const positionId = Number(position?.id);

        if (Number.isInteger(positionId) && position?.name) {
          positionNameById.set(positionId, position.name);
        }
      });
    }
  } catch (error) {
    console.warn('Unable to resolve positions list.', error);
  }

  return positionNameById;
}

function normalizePlayer(player = {}, lookups = {}) {
  const { countryById = new Map(), positionById = new Map() } = lookups;
  const countryId = Number(player.country_id || player.country?.id || player.country?.data?.id || 0) || null;
  const positionId = Number(player.position_id || player.position?.id || player.position?.data?.id || 0) || null;

  const countryName =
    player.country?.name ||
    player.country?.data?.name ||
    (countryId ? countryById.get(countryId) : '') ||
    player.country_name ||
    player.nationality ||
    'Unknown';

  const positionName =
    player.position?.name ||
    player.position?.resource ||
    (positionId ? positionById.get(positionId) : '') ||
    player.playing_role ||
    player.role ||
    player.position_name ||
    'Unknown';

  return {
    id: player.id,
    fullName: player.fullname || `${player.firstname || ''} ${player.lastname || ''}`.trim() || 'Unknown Player',
    firstName: player.firstname || '',
    lastName: player.lastname || '',
    imageUrl: player.image_path || player.image || '',
    country: countryName,
    position: positionName,
    dateOfBirth: player.dateofbirth || player.dob || '',
    battingStyle: player.battingstyle || '',
    bowlingStyle: player.bowlingstyle || '',
    gender: player.gender || '',
    updatedAt: player.updated_at || '',
    teamName: player.team?.name || player.team?.data?.name || '',
    teams: extractTeamNames(player.teams),
    tournamentTypes: extractTournamentTypesFromCareer(player.career),
    careerStats: extractCareerStats(player.career),
  };
}

export async function fetchPlayersDataset() {
  const payload = await fetchWithRetry(buildUrl('/players'));
  const playersChunk = payload?.data || [];

  if (!Array.isArray(playersChunk) || playersChunk.length === 0) {
    return [];
  }

  const countryIds = playersChunk.map((player) => player?.country_id || player?.country?.id || player?.country?.data?.id);
  const [countryById, positionById] = await Promise.all([fetchCountryMap(countryIds), fetchPositionMap()]);

  return Array.isArray(playersChunk)
    ? playersChunk
        .map((player) => normalizePlayer(player, { countryById, positionById }))
        .filter((player) => Boolean(player.id))
    : [];
}

export async function fetchPlayerById(playerId) {
  const url = buildUrl(`/players/${playerId}`, {
    include: 'country,career,career.season,teams',
  });

  const payload = await fetchWithRetry(url);
  const player = payload?.data;

  if (!player) {
    throw new Error('Player not found.');
  }

  const countryId = player?.country_id || player?.country?.id || player?.country?.data?.id;
  const [countryById, positionById] = await Promise.all([fetchCountryMap([countryId]), fetchPositionMap()]);

  return normalizePlayer(player, { countryById, positionById });
}
