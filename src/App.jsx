import { Navigate, Route, Routes } from 'react-router-dom';
import PlayersPage from './pages/PlayersPage';
import PlayerDetailPage from './pages/PlayerDetailPage';

function App() {
  return (
    <Routes>
      <Route path="/" element={<PlayersPage />} />
      <Route path="/players/:playerId" element={<PlayerDetailPage />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
