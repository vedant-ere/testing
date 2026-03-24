import { Link } from 'react-router-dom';
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

function PlayerCard({ player }) {
  const imageSource = playerPlaceholderImage;

  return (
    <article className="player-card">
      <Link className="player-link" to={`/players/${player.id}`}>
        <div className="player-card-media">
          <img src={imageSource} alt={player.fullName} className="player-image" loading="lazy" />
        </div>

        <div className="player-content">
          <h2 className="card-name">{player.fullName}</h2>

          <div className="meta-grid">
            <p>
              <strong>Country</strong>
              <span>{player.country || 'Unknown'}</span>
            </p>
            <p>
              <strong>Role</strong>
              <span>{player.position || 'Cricketer'}</span>
            </p>
            <p className="meta-wide">
              <strong>Date of Birth</strong>
              <span>{formatDate(player.dateOfBirth)}</span>
            </p>
          </div>

          <span className="view-more">View details</span>
        </div>
      </Link>
    </article>
  );
}

export default PlayerCard;
