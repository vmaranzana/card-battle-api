import {Routes, Route } from 'react-router-dom';
import StatPage from './pages/stat/statPage';
import AddMazoPage from './pages/mazo/addMazoPage';
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';
import EditarUsuarioPage from './pages/editar/EditarUsuarioPage.jsx';
import MisMazosPage from './pages/mazos/MisMazosPage';
import JugarPage from './pages/Jugar/JugarPage';

function App() {
  return (
    <Routes>
      <Route path="/" element={<StatPage />} />
      <Route path="/crear-mazo" element={<AddMazoPage />} />
      <Route path="/registro" element={<RegistroPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/editar" element={<EditarUsuarioPage />} />
      <Route path="/mis-mazos" element={<MisMazosPage />} />
      <Route path="/jugar/:idPartida" element={<JugarPage />} />
    </Routes>
  );
}


export default App;

