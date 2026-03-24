import { Link, useLocation } from 'react-router-dom';

function AppHeader() {
  const location = useLocation();
  const isPlayersPage = location.pathname === '/';

  return (
    <header className="cb-topbar">
      <div className="cb-topbar-inner">
        <div className="cb-label">Player Directory</div>

        <nav className="cb-nav" aria-label="Primary">
          <Link to="/" className={isPlayersPage ? 'active' : ''}>
            All Players
          </Link>
        </nav>
      </div>
    </header>
  );
}

export default AppHeader;
