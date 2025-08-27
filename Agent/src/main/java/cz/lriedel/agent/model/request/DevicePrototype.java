package cz.lriedel.agent.model.request;

public record DevicePrototype(String type, String token) {

    public DevicePrototype(String token) {
        this("AGENT", token);
    }
}
