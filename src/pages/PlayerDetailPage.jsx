import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import AppHeader from '../components/AppHeader';
import { fetchPlayerById } from '../services/api';
import playerPlaceholderImage from '../assets/player-placeholder.svg';

function formatDate(dateValue) {
  if (!dateValue) {
    return 'N/A';
  }

  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) {
    return dateValue;
  }

  return date.toLocaleDateString();
}

function formatStatValue(value) {
  if (value === null || value === undefined || value === '') {
    return 'N/A';
  }

  if (typeof value === 'number') {
    if (Number.isInteger(value)) {
      return value.toLocaleString();
    }

    return value.toFixed(2);
  }

  return String(value);
}

function PlayerDetailPage() {
  const { playerId } = useParams();
  const [player, setPlayer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const imageSource = playerPlaceholderImage;

  useEffect(() => {
    async function loadPlayer() {
      if (!playerId) {
        setError('Invalid player ID.');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError('');
        const data = await fetchPlayerById(playerId);
        setPlayer(data);
      } catch (apiError) {
        setError(apiError?.message || 'Unable to fetch player details.');
      } finally {
        setLoading(false);
      }
    }

    loadPlayer();
  }, [playerId]);

  return (
    <>
      <AppHeader />
      <main className="page-shell">
        <header className="detail-header">
          <Link to="/" className="back-link">
            Back to Players
          </Link>
        </header>

        {loading && <p className="state-text">Loading player details...</p>}
        {!loading && error && <p className="state-text error">{error}</p>}

        {!loading && !error && player && (
          <div className="detail-sections">
            <section className="detail-card detail-hero-card">
              <div className="detail-top">
                <img src={imageSource} alt={player.fullName} className="detail-image" />

                <div className="detail-summary">
                  <h1>{player.fullName}</h1>
                  <div className="detail-inline">
                    <span>{player.country || 'Unknown'}</span>
                    <span className="role-chip">{player.position || 'Cricketer'}</span>
                  </div>
                  <ul className="detail-facts">
                    <li>{formatDate(player.dateOfBirth)}</li>
                    <li>{player.gender || 'N/A'}</li>
                    <li>{player.battingStyle || 'N/A'}</li>
                    <li>{player.bowlingStyle || 'N/A'}</li>
                  </ul>
                </div>
              </div>
            </section>

            <section className="detail-card">
              <h2>Career Stats</h2>
              {player.careerStats.length > 0 ? (
                player.careerStats.map((stat) => (
                  <div key={stat.type} className="career-block">
                    <h3>{stat.type}</h3>
                    <div className="career-grid">
                      <article className="career-stat">
                        <span>Matches</span>
                        <strong>{formatStatValue(stat.matches)}</strong>
                      </article>
                      <article className="career-stat">
                        <span>Runs</span>
                        <strong>{formatStatValue(stat.runs)}</strong>
                      </article>
                      <article className="career-stat">
                        <span>Wickets</span>
                        <strong>{formatStatValue(stat.wickets)}</strong>
                      </article>
                      <article className="career-stat">
                        <span>Average</span>
                        <strong>{formatStatValue(stat.average)}</strong>
                      </article>
                    </div>
                  </div>
                ))
              ) : (
                <p>No career statistics available.</p>
              )}
            </section>

            <section className="detail-card">
              <h2>Teams</h2>
              {player.teams.length > 0 ? (
                <ul className="team-chips">
                  {player.teams.map((team) => (
                    <li key={team}>{team}</li>
                  ))}
                </ul>
              ) : player.teamName ? (
                <ul className="team-chips">
                  <li>{player.teamName}</li>
                </ul>
              ) : (
                <p>No team information available.</p>
              )}
            </section>
          </div>
        )}
      </main>
    </>
  );
}

export default PlayerDetailPage;
