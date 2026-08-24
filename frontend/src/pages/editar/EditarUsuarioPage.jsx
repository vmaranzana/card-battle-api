import { useState, useEffect } from "react";
import { editUser } from '../../services/apiServices.js';
import { useNavigate } from "react-router-dom"; 
import Layout from '../../layout/Layout.jsx';
import '../../assets/styles/Forms.css'; 

function EditarUsuarioPage() {
    const [errores, setErrores] = useState([]);
    const navigate = useNavigate(); // Inicializamos useNavigate

    const handleSubmit = async (e) => {
        e.preventDefault();
        const nombre = e.target[0].value.trim();
        const password = e.target[1].value;
        const passwordRep = e.target[2].value;

        const nuevosErrores = [];

        if (!nombre) {
            nuevosErrores.push("El nombre no puede estar vacío.");
        } else if (nombre.length > 30) {
            nuevosErrores.push("El nombre no puede tener más de 30 caracteres.");
        }

        if (password.length < 8) {
            nuevosErrores.push("La contraseña debe tener al menos 8 caracteres.");
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{}[\]:;"'<>,.?/\\|]).{8,}/.test(password)) {
            nuevosErrores.push("La contraseña debe tener mayúsculas, minúsculas, números y caracteres especiales.");
        }

        if (password !== passwordRep) {
            nuevosErrores.push("Las contraseñas no coinciden");
        }

        if (nuevosErrores.length > 0) {
            setErrores(nuevosErrores);
            return;
        }

        const token = localStorage.getItem("token");
        const userId = localStorage.getItem("id");
        const data = { nombre, password };

        try {
            await editUser(userId, data, token);
            console.log("Perfil actualizado con éxito"); 
            setErrores([]);
        } catch (error) {
            if (error.response?.data?.error) {
                setErrores([error.response.data.error]);
            } else {
                setErrores(["Error al actualizar el usuario."]);
            }
        }
    };

    const handleGoBack = () => {
        navigate("/"); 
    };

    return (
        <Layout>
            <div className="container d-flex justify-content-center align-items-center min-vh-100 py-2">
                <div className="left p-4 rounded shadow-lg mx-auto col-12 col-md-8 col-lg-6">
                    {errores.length > 0 && (
                          <div className="alert alert-danger" role="alert">
                            <ul className="mb-0 ps-3"> 
                                {errores.map((err, i) => (
                                    <li key={i}>{err}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <h2 className="title text-center mb-4">EDITAR USUARIO</h2>

                    <form className='form needs-validation' onSubmit={handleSubmit} noValidate>
                        <div className="mb-3"> 
                            <label htmlFor="nombreInput" className="form-label">Ingresa tu nombre</label>
                            <input
                                type="text"
                                className="form-control"
                                name="nombre"
                                placeholder="Tu nuevo nombre"
                            />
                        </div>

                        <div className="mb-3"> 
                            <label htmlFor="passwordInput" className="form-label">Ingresa nueva contraseña</label>
                            <input
                                type="password"
                                className="form-control"
                                name="password"
                                placeholder="Nueva contraseña"
                            />
                        </div>

                        <div className="mb-4">
                            <label htmlFor="passwordRepInput" className="form-label">Repetir contraseña</label>
                            <input
                                type="password"
                                className="form-control"
                                name="passwordRep"
                                placeholder="Repite la nueva contraseña"
                            />
                        </div>

                        <div className="d-flex justify-content-between gap-2 flex-column flex-md-row mt-3">
                            <button type="submit" className="btn btn-primary flex-grow-1">Editar</button>
                            <button type="button" className="btn btn-outline-secondary-custom flex-grow-1" onClick={handleGoBack}>
                                Volver al Inicio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Layout>
    );
}

export default EditarUsuarioPage;
